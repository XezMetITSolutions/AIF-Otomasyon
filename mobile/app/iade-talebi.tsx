import React, { useState, useEffect } from 'react';
import { 
  StyleSheet, 
  ScrollView, 
  TextInput, 
  Pressable, 
  Alert, 
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  Modal,
  FlatList,
  Image
} from 'react-native';
import { Stack, router } from 'expo-router';
import { FontAwesome6 } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import { submitIadeTalebi } from '@/services/api';

const PROJECT_COLORS = {
  primary: '#009872',
  secondary: '#004d3a',
  bgSoft: '#f8fafc',
};

const BYK_OPTIONS = ['AT', 'KT', 'GT', 'KGT'];
const BIRIM_OPTIONS = [
  'Başkan', 'BYK Üyesi', 'Eğitim', 'Fuar', 'Spor/Gezi (GOB)', 'Hac/Umre', 
  'İdari İşler', 'İrşad', 'Kurumsal İletişim', 'Muhasebe', 'Orta Öğretim', 
  'Raggal', 'Sosyal Hizmetler', 'Tanıtma', 'Teftiş', 'Teşkilatlanma', 'Üniversiteler'
];
const TYPE_OPTIONS = [
  'Genel', 'Ulaşım - Kilometre', 'Ulaşım - Faturalı', 'Yemek/İkram', 'Konaklama', 'Malzeme'
];
const PAYMENT_OPTIONS = ['Faturasız', 'Faturalı'];
const MONTHS = ['01','02','03','04','05','06','07','08','09','10','11','12'];

type ExpenseItem = {
  id: string;
  date: string;
  region: string;
  birim: string;
  type: string;
  paymentMode: string;
  description: string;
  amount: string;
  // Detaylı Faturalı Alanlar
  net: string;
  mwst: string;
  // Kilometre Alanları
  startLoc: string;
  endLoc: string;
  km: string;
  // Fotoğraf
  image?: string;
};

export default function IadeTalebiScreen() {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  const [loading, setLoading] = useState(false);
  const [calcLoading, setCalcLoading] = useState<string | null>(null);
  const [user, setUser] = useState<any>(null);
  const [iban, setIban] = useState('');
  const [items, setItems] = useState<ExpenseItem[]>([{
    id: Math.random().toString(36).substr(2, 9),
    date: new Date().toISOString().split('T')[0],
    region: 'AT',
    birim: 'Teşkilatlanma',
    type: 'Genel',
    paymentMode: 'Faturalı',
    description: '',
    amount: '',
    net: '',
    mwst: '',
    startLoc: '',
    endLoc: '',
    km: ''
  }]);

  const [modalVisible, setModalVisible] = useState(false);
  const [modalType, setModalType] = useState<any>(null);
  const [activeItemId, setActiveItemId] = useState<string | null>(null);
  const [selectedMonth, setSelectedMonth] = useState(new Date().toISOString().split('-')[1]);

  useEffect(() => {
    const loadUser = async () => {
      const data = await AsyncStorage.getItem('user');
      if (data) setUser(JSON.parse(data));
    };
    loadUser();
  }, []);

  const addItem = () => {
    setItems([...items, {
      id: Math.random().toString(36).substr(2, 9),
      date: new Date().toISOString().split('T')[0],
      region: 'AT',
      birim: 'Teşkilatlanma',
      type: 'Genel',
      paymentMode: 'Faturalı',
      description: '',
      amount: '',
      net: '',
      mwst: '',
      startLoc: '',
      endLoc: '',
      km: ''
    }]);
  };

  const removeItem = (id: string) => {
    if (items.length === 1) return;
    setItems(items.filter(i => i.id !== id));
  };

  const updateItem = (id: string, field: keyof ExpenseItem, value: string) => {
    setItems(items.map(i => {
      if (i.id !== id) return i;
      const updated = { ...i, [field]: value };
      
      // Fatura Otomatik Hesaplama
      if (field === 'net' || field === 'mwst') {
        const n = parseFloat(updated.net.replace(',', '.')) || 0;
        const m = parseFloat(updated.mwst.replace(',', '.')) || 0;
        updated.amount = (n + m).toFixed(2);
      }
      
      return updated;
    }));
  };

  const calculateDistance = async (id: string) => {
    const item = items.find(i => i.id === id);
    if (!item?.startLoc || !item?.endLoc) {
      Alert.alert('Hata', 'Lütfen başlangıç ve bitiş adreslerini giriniz.');
      return;
    }

    setCalcLoading(id);
    try {
      // OSRM Public API (Demo için basitleştirilmiş koordinat bulma simülasyonu)
      // Gerçek prodüksiyonda Geocoding API ile koordinat alınmalıdır.
      // Şimdilik demo mesafe ata (simülasyon)
      await new Promise(r => setTimeout(r, 1500));
      const mockKm = (Math.random() * 50 + 10).toFixed(2);
      const mockAmt = (parseFloat(mockKm) * 0.25).toFixed(2);
      
      setItems(items.map(i => i.id === id ? { ...i, km: mockKm, amount: mockAmt } : i));
    } catch (e) {
      Alert.alert('Hata', 'Mesafe hesaplanamadı.');
    } finally {
      setCalcLoading(null);
    }
  };

  const handleSelect = (value: string) => {
    if (activeItemId && modalType && modalType !== 'date') {
      updateItem(activeItemId, modalType as any, value);
    }
    setModalVisible(false);
  };

  const handleDateSelect = (day: string) => {
    if (activeItemId) {
      const year = new Date().getFullYear();
      const formattedDate = `${year}-${selectedMonth}-${day.padStart(2, '0')}`;
      updateItem(activeItemId, 'date', formattedDate);
    }
    setModalVisible(false);
  };

  const getOptions = () => {
    if (modalType === 'region') return BYK_OPTIONS;
    if (modalType === 'birim') return BIRIM_OPTIONS;
    if (modalType === 'type') return TYPE_OPTIONS;
    if (modalType === 'paymentMode') return PAYMENT_OPTIONS;
    if (modalType === 'date') return Array.from({ length: 31 }, (_, i) => (i + 1).toString());
    return [];
  };

  const calculateTotal = () => {
    return items.reduce((sum, item) => sum + (parseFloat(item.amount.replace(',', '.')) || 0), 0);
  };

  const formatIban = (text: string) => {
    const cleaned = text.replace(/\s+/g, '').toUpperCase();
    const formatted = cleaned.match(/.{1,4}/g)?.join(' ') || cleaned;
    setIban(formatted);
  };

  const handleSubmit = async () => {
    if (!user) return;
    if (!iban || iban.length < 15) {
      Alert.alert('Hata', 'Lütfen geçerli bir IBAN giriniz.');
      return;
    }

    const invalidItems = items.filter(i => !i.amount || !i.description);
    if (invalidItems.length > 0) {
      Alert.alert('Hata', 'Lütfen tüm gider kalemlerini doldurunuz.');
      return;
    }

    setLoading(true);
    try {
      const total = calculateTotal();
      const result = await submitIadeTalebi(user.id, items, iban, total);
      if (result.success) {
        Alert.alert('Başarılı', result.message, [
          { text: 'Tamam', onPress: () => router.back() }
        ]);
      } else {
        Alert.alert('Hata', result.message);
      }
    } catch (error) {
      Alert.alert('Hata', 'Sunucuya ulaşılamadı.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView 
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      style={[styles.container, { backgroundColor: colorScheme === 'light' ? PROJECT_COLORS.bgSoft : theme.background }]}
    >
      <Stack.Screen options={{ title: 'İade Talebi Pro' }} />
      
      <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
        <View style={styles.header}>
          <Text style={styles.headerTitle}>Harcama Bildirimi</Text>
          <Text style={styles.headerSubtitle}>Giderlerinizi detaylıca girerek iade talebi oluşturun.</Text>
        </View>

        {items.map((item, index) => (
          <View key={item.id} style={[styles.card, { backgroundColor: theme.card, borderColor: theme.border }]}>
            <View style={styles.cardHeader}>
              <View style={styles.badge}><Text style={styles.badgeText}>ITEM #{index + 1}</Text></View>
              {items.length > 1 && (
                <Pressable onPress={() => removeItem(item.id)} style={styles.removeBtn}>
                  <FontAwesome6 name="trash-can" size={14} color="#ef4444" />
                </Pressable>
              )}
            </View>

            {/* Row 1: Date & BYK */}
            <View style={styles.row}>
                <View style={styles.inputGroupHalf}>
                    <Text style={[styles.label, { color: theme.text }]}>Tarih</Text>
                    <Pressable 
                        style={[styles.pickerFake, { backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f1f5f9' }]}
                        onPress={() => openSelector(item.id, 'date')}
                    >
                        <Text style={{ color: theme.text, fontWeight: '600' }}>{item.date}</Text>
                        <FontAwesome6 name="calendar-day" size={12} color={PROJECT_COLORS.primary} />
                    </Pressable>
                </View>
                <View style={styles.inputGroupHalf}>
                    <Text style={[styles.label, { color: theme.text }]}>BYK</Text>
                    <Pressable 
                        style={[styles.pickerFake, { backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f1f5f9' }]}
                        onPress={() => openSelector(item.id, 'region')}
                    >
                        <Text style={{ color: theme.text, fontWeight: '600' }}>{item.region}</Text>
                        <FontAwesome6 name="chevron-down" size={10} color="#94a3b8" />
                    </Pressable>
                </View>
            </View>

            {/* Row 2: Birim & Type */}
            <View style={styles.row}>
              <View style={styles.inputGroupHalf}>
                <Text style={[styles.label, { color: theme.text }]}>Birim</Text>
                <Pressable 
                    style={[styles.pickerFake, { backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f1f5f9' }]}
                    onPress={() => openSelector(item.id, 'birim')}
                >
                    <Text style={{ color: theme.text, fontWeight: '600' }} numberOfLines={1}>{item.birim}</Text>
                    <FontAwesome6 name="chevron-down" size={10} color="#94a3b8" />
                </Pressable>
              </View>
              <View style={styles.inputGroupHalf}>
                <Text style={[styles.label, { color: theme.text }]}>Tür</Text>
                <Pressable 
                    style={[styles.pickerFake, { backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f1f5f9' }]}
                    onPress={() => openSelector(item.id, 'type')}
                >
                    <Text style={{ color: theme.text, fontWeight: '600' }} numberOfLines={1}>{item.type}</Text>
                    <FontAwesome6 name="chevron-down" size={10} color="#94a3b8" />
                </Pressable>
              </View>
            </View>

            {/* Kilometre Alanları (Eğer Tür Km ise) */}
            {item.type === 'Ulaşım - Kilometre' && (
                <View style={styles.kmBox}>
                    <Text style={styles.kmBoxTitle}>Mesafe Hesapla</Text>
                    <TextInput 
                        style={[styles.inputSm, { backgroundColor: colorScheme === 'dark' ? '#0f172a' : '#fff' }]}
                        placeholder="Nereden?"
                        value={item.startLoc}
                        onChangeText={(v) => updateItem(item.id, 'startLoc', v)}
                    />
                    <TextInput 
                        style={[styles.inputSm, { backgroundColor: colorScheme === 'dark' ? '#0f172a' : '#fff', marginTop: 8 }]}
                        placeholder="Nereye?"
                        value={item.endLoc}
                        onChangeText={(v) => updateItem(item.id, 'endLoc', v)}
                    />
                    <Pressable 
                        style={[styles.calcBtn, { opacity: calcLoading === item.id ? 0.6 : 1 }]} 
                        onPress={() => calculateDistance(item.id)}
                        disabled={calcLoading === item.id}
                    >
                        {calcLoading === item.id ? <ActivityIndicator size="small" color="#fff" /> : 
                        <><FontAwesome6 name="route" size={12} color="#fff" /><Text style={styles.calcBtnText}>Mesafeyi Hesapla (OSRM)</Text></>}
                    </Pressable>
                    {item.km && <Text style={styles.kmResult}>Mesafe: {item.km} km (0.25€/km)</Text>}
                </View>
            )}

            {/* Row 3: Payment Mode & Amount */}
            <View style={styles.row}>
                <View style={styles.inputGroupHalf}>
                    <Text style={[styles.label, { color: theme.text }]}>Ödeme Şekli</Text>
                    <Pressable 
                        style={[styles.pickerFake, { backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f1f5f9' }]}
                        onPress={() => openSelector(item.id, 'paymentMode')}
                    >
                        <Text style={{ color: theme.text, fontWeight: '600' }}>{item.paymentMode}</Text>
                        <FontAwesome6 name="chevron-down" size={10} color="#94a3b8" />
                    </Pressable>
                </View>
                <View style={styles.inputGroupHalf}>
                    <Text style={[styles.label, { color: theme.text }]}>Toplam Tutar (€)</Text>
                    <TextInput 
                        style={[styles.input, { backgroundColor: (item.paymentMode === 'Faturalı' || item.type === 'Ulaşım - Kilometre') ? '#e2e8f0' : (colorScheme === 'dark' ? '#1e293b' : '#f1f5f9'), color: theme.text, fontWeight: '700' }]}
                        value={item.amount}
                        editable={item.paymentMode !== 'Faturalı' && item.type !== 'Ulaşım - Kilometre'}
                        onChangeText={(val) => updateItem(item.id, 'amount', val)}
                        keyboardType="numeric"
                        placeholder="0,00"
                    />
                </View>
            </View>

            {/* Faturalı Detay Alanları */}
            {item.paymentMode === 'Faturalı' && (
                <View style={styles.nestedRow}>
                    <View style={styles.inputGroupThird}>
                        <Text style={styles.labelSub}>Net (€)</Text>
                        <TextInput 
                            style={[styles.inputSm, { backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f1f5f9', color: theme.text }]}
                            value={item.net}
                            onChangeText={(v) => updateItem(item.id, 'net', v)}
                            keyboardType="numeric"
                            placeholder="Net"
                        />
                    </View>
                    <View style={styles.inputGroupThird}>
                        <Text style={styles.labelSub}>KDV (€)</Text>
                        <TextInput 
                            style={[styles.inputSm, { backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f1f5f9', color: theme.text }]}
                            value={item.mwst}
                            onChangeText={(v) => updateItem(item.id, 'mwst', v)}
                            keyboardType="numeric"
                            placeholder="KDV"
                        />
                    </View>
                    <View style={styles.inputGroupThird}>
                        <Text style={styles.labelSub}>BRÜT (€)</Text>
                        <View style={[styles.inputSm, { backgroundColor: '#e2e8f0', justifyContent: 'center' }]}>
                             <Text style={{ fontWeight: '700', fontSize: 12 }}>{item.amount || '0.00'}</Text>
                        </View>
                    </View>
                </View>
            )}

            {/* Açıklama & Fotoğraf */}
            <View style={styles.footerRow}>
                <View style={{ flex: 1 }}>
                    <Text style={[styles.label, { color: theme.text }]}>Açıklama</Text>
                    <TextInput 
                        style={[styles.input, { backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f1f5f9', color: theme.text }]}
                        value={item.description}
                        onChangeText={(val) => updateItem(item.id, 'description', val)}
                        placeholder="Harcamanın sebebi..."
                    />
                </View>
                <Pressable style={styles.fileBtn} onPress={() => Alert.alert('Dosya Seçimi', 'Lütfen kameranızdan fiş fotoğrafını çekin.')}>
                    <FontAwesome6 name="camera" size={18} color={PROJECT_COLORS.primary} />
                    <Text style={styles.fileBtnText}>FİŞ EKLE</Text>
                </Pressable>
            </View>
          </View>
        ))}

        <Pressable style={styles.addButton} onPress={addItem}>
          <FontAwesome6 name="plus-circle" size={18} color={PROJECT_COLORS.primary} />
          <Text style={styles.addButtonText}>Yeni Kalem Ekle</Text>
        </Pressable>

        <View style={[styles.card, { backgroundColor: theme.card, borderColor: theme.border }]}>
          <Text style={[styles.label, { color: theme.text }]}>IBAN Numaranız</Text>
          <View style={[styles.inputWrapper, { backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f1f5f9' }]}>
            <FontAwesome6 name="building-columns" size={16} color={theme.tabIconDefault} style={styles.inputIcon} />
            <TextInput 
              style={[styles.inputField, { color: theme.text }]}
              value={iban}
              onChangeText={formatIban}
              placeholder="ATXX XXXX XXXX XXXX"
              placeholderTextColor="#94a3b8"
              autoCapitalize="characters"
            />
          </View>
          <Text style={styles.ibanNotice}>Lütfen geçerli bir IBAN girdiğinizden emin olun.</Text>
        </View>

        <View style={styles.totalBox}>
            <LinearGradient colors={[PROJECT_COLORS.primary, PROJECT_COLORS.secondary]} style={styles.totalGrad}>
                <Text style={styles.totalLabel}>Toplam İade Tutarı</Text>
                <Text style={styles.totalValue}>{calculateTotal().toFixed(2)} €</Text>
            </LinearGradient>
        </View>

        <Pressable 
          disabled={loading} 
          onPress={handleSubmit}
          style={({ pressed }) => [
            styles.submitButton,
            { backgroundColor: PROJECT_COLORS.primary, opacity: (pressed || loading) ? 0.8 : 1 }
          ]}
        >
          {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.submitButtonText}>Talebi Gönder</Text>}
        </Pressable>
      </ScrollView>

      {/* GLOBAL SELECT MODAL */}
      <Modal visible={modalVisible} transparent={true} animationType="slide">
        <View style={styles.modalBg}>
            <View style={[styles.modalContent, { backgroundColor: theme.card }]}>
                <View style={styles.modalHeader}>
                    <Text style={[styles.modalTitle, { color: theme.text }]}>
                        {modalType === 'date' ? 'Tarih Seçin' : 'Seçim Yapın'}
                    </Text>
                    <Pressable onPress={() => setModalVisible(false)}><FontAwesome6 name="xmark" size={20} color={theme.text} /></Pressable>
                </View>

                {modalType === 'date' && (
                    <View style={styles.monthSelector}>
                        <ScrollView horizontal showsHorizontalScrollIndicator={false}>
                            {MONTHS.map(m => (
                                <Pressable 
                                    key={m} 
                                    onPress={() => setSelectedMonth(m)}
                                    style={[styles.monthPill, { backgroundColor: selectedMonth === m ? PROJECT_COLORS.primary : (colorScheme === 'dark' ? '#1e293b' : '#f1f5f9') }]}
                                >
                                    <Text style={{ color: selectedMonth === m ? '#fff' : theme.text, fontWeight: '700', fontSize: 12 }}>{m}. AY</Text>
                                </Pressable>
                            ))}
                        </ScrollView>
                    </View>
                )}
                
                <FlatList 
                    data={getOptions()}
                    keyExtractor={(item) => item}
                    numColumns={modalType === 'date' ? 4 : 1}
                    contentContainerStyle={{ paddingBottom: 30 }}
                    renderItem={({ item }) => (
                        <Pressable 
                            style={modalType === 'date' ? styles.dateItem : styles.optionItem} 
                            onPress={() => modalType === 'date' ? handleDateSelect(item) : handleSelect(item)}
                        >
                            <Text style={[modalType === 'date' ? styles.dateText : styles.optionText, { color: theme.text }]}>{item}</Text>
                        </Pressable>
                    )}
                />
            </View>
        </View>
      </Modal>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  scrollContent: { padding: 16, paddingBottom: 60 },
  header: { marginBottom: 20, paddingHorizontal: 4 },
  headerTitle: { fontSize: 28, fontWeight: '900', marginBottom: 4 },
  headerSubtitle: { fontSize: 13, color: '#64748b', lineHeight: 18 },
  card: { padding: 16, borderRadius: 24, borderWidth: 1, marginBottom: 16, elevation: 4, shadowColor: '#000', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.1, shadowRadius: 10 },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 16, alignItems: 'center' },
  badge: { backgroundColor: 'rgba(0,152,114,0.1)', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 8 },
  badgeText: { fontSize: 10, fontWeight: '900', color: PROJECT_COLORS.primary },
  removeBtn: { width: 28, height: 28, borderRadius: 14, backgroundColor: 'rgba(239, 68, 68, 0.1)', justifyContent: 'center', alignItems: 'center' },
  row: { flexDirection: 'row', gap: 10, marginBottom: 10 },
  nestedRow: { flexDirection: 'row', gap: 8, marginTop: 4, marginBottom: 12, backgroundColor: 'rgba(0,0,0,0.02)', padding: 10, borderRadius: 12 },
  kmBox: { backgroundColor: 'rgba(0,152,114,0.05)', padding: 12, borderRadius: 16, marginBottom: 12, borderWidth: 1, borderColor: 'rgba(0,152,114,0.2)' },
  kmBoxTitle: { fontSize: 11, fontWeight: '800', color: PROJECT_COLORS.primary, marginBottom: 8, textTransform: 'uppercase' },
  kmResult: { fontSize: 12, fontWeight: '700', color: PROJECT_COLORS.primary, marginTop: 8 },
  calcBtn: { backgroundColor: PROJECT_COLORS.primary, height: 36, borderRadius: 10, marginTop: 10, flexDirection: 'row', justifyContent: 'center', alignItems: 'center', gap: 6 },
  calcBtnText: { color: '#fff', fontSize: 11, fontWeight: '700' },
  inputGroupHalf: { flex: 0.5 },
  inputGroupThird: { flex: 0.33 },
  label: { fontSize: 11, fontWeight: '800', marginBottom: 6, marginLeft: 4, textTransform: 'uppercase', opacity: 0.7 },
  labelSub: { fontSize: 10, fontWeight: '700', marginBottom: 4, opacity: 0.6 },
  input: { height: 48, borderRadius: 14, paddingHorizontal: 14, fontSize: 14, fontWeight: '500' },
  inputSm: { height: 40, borderRadius: 10, paddingHorizontal: 12, fontSize: 12, fontWeight: '600' },
  pickerFake: { height: 48, borderRadius: 14, paddingHorizontal: 14, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  footerRow: { flexDirection: 'row', gap: 10, alignItems: 'flex-end', marginTop: 10 },
  fileBtn: { width: 80, height: 75, borderRadius: 16, borderWidth: 1, borderStyle: 'dashed', borderColor: PROJECT_COLORS.primary, justifyContent: 'center', alignItems: 'center', backgroundColor: '#fff' },
  fileBtnText: { fontSize: 9, fontWeight: '800', color: PROJECT_COLORS.primary, marginTop: 6 },
  addButton: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', padding: 16, borderRadius: 20, borderWidth: 2, borderStyle: 'dashed', borderColor: PROJECT_COLORS.primary, marginVertical: 10 },
  addButtonText: { marginLeft: 10, color: PROJECT_COLORS.primary, fontWeight: '800', fontSize: 15 },
  inputWrapper: { flexDirection: 'row', alignItems: 'center', height: 60, borderRadius: 18, paddingHorizontal: 16 },
  inputIcon: { marginRight: 12 },
  inputField: { flex: 1, fontSize: 16, fontWeight: '700' },
  ibanNotice: { fontSize: 10, color: '#94a3b8', marginTop: 8, textAlign: 'center' },
  totalBox: { marginVertical: 20 },
  totalGrad: { padding: 24, borderRadius: 24, alignItems: 'center' },
  totalLabel: { color: 'rgba(255,255,255,0.8)', fontSize: 14, fontWeight: '600', marginBottom: 4 },
  totalValue: { color: '#fff', fontSize: 36, fontWeight: '900' },
  submitButton: { height: 64, borderRadius: 32, justifyContent: 'center', alignItems: 'center', elevation: 8, shadowColor: PROJECT_COLORS.primary, shadowOffset: { width: 0, height: 6 }, shadowOpacity: 0.3, shadowRadius: 12 },
  submitButtonText: { color: '#ffffff', fontSize: 20, fontWeight: '900' },
  modalBg: { flex: 1, backgroundColor: 'rgba(0,0,0,0.6)', justifyContent: 'flex-end' },
  modalContent: { borderTopLeftRadius: 36, borderTopRightRadius: 36, padding: 24, maxHeight: '85%' },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 },
  modalTitle: { fontSize: 20, fontWeight: '900' },
  monthSelector: { marginBottom: 15, paddingBottom: 5 },
  monthPill: { paddingHorizontal: 18, paddingVertical: 10, borderRadius: 24, marginRight: 8, borderWidth: 1, borderColor: '#e2e8f0' },
  optionItem: { paddingVertical: 20, borderBottomWidth: 1, borderBottomColor: '#f1f5f9' },
  optionText: { fontSize: 17, fontWeight: '600' },
  dateItem: { flex: 1, height: 60, justifyContent: 'center', alignItems: 'center', margin: 4, borderRadius: 16, backgroundColor: '#f1f5f9' },
  dateText: { fontSize: 18, fontWeight: '800' }
});
