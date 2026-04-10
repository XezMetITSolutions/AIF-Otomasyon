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
  Image,
  Dimensions
} from 'react-native';
import { Stack, router } from 'expo-router';
import { FontAwesome6 } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { LinearGradient } from 'expo-linear-gradient';
import * as ImagePicker from 'expo-image-picker';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import { submitIadeTalebi } from '@/services/api';

const { width } = Dimensions.get('window');
const PROJECT_COLORS = {
  primary: '#009872',
  secondary: '#004d3a',
  accent: '#f59e0b',
  bgSoft: '#f8fafc',
};

const ORS_API_KEY = 'eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6IjdiYWRhNGRlODEwNjQ1ZjY4NmI0MmMzZDgwOTExODJlIiwiaCI6Im11cm11cjY0In0=';

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
  net: string;
  mwst: string;
  startLoc: string;
  endLoc: string;
  km: string;
  isRoundTrip: boolean;
  image?: string;
};

export default function IadeTalebiScreen() {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  const isDark = colorScheme === 'dark';
  const labelColor = isDark ? '#94a3b8' : '#64748b';
  const inputBg = isDark ? '#1e293b' : '#f1f5f9';
  const disabledBg = isDark ? '#0f172a' : '#e2e8f0';
  const cardBg = theme.card;

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
    km: '',
    isRoundTrip: true
  }]);

  const [modalVisible, setModalVisible] = useState(false);
  const [modalType, setModalType] = useState<any>(null);
  const [activeItemId, setActiveItemId] = useState<string | null>(null);
  const [selectedMonth, setSelectedMonth] = useState(new Date().toISOString().split('-')[1]);
  const [suggestions, setSuggestions] = useState<any[]>([]);
  const [activeInputType, setActiveInputType] = useState<'start' | 'end' | null>(null);

  useEffect(() => {
    (async () => {
      if (Platform.OS !== 'web') {
        const { status } = await ImagePicker.requestCameraPermissionsAsync();
        const { status: libStatus } = await ImagePicker.requestMediaLibraryPermissionsAsync();
        if (status !== 'granted' || libStatus !== 'granted') {
          Alert.alert('İzin Gerekli', 'Kamera ve Galeri erişimi için izin vermelisiniz.');
        }
      }
      const data = await AsyncStorage.getItem('user');
      if (data) setUser(JSON.parse(data));
    })();
  }, []);

  const pickImage = async (id: string, mode: 'camera' | 'library') => {
    let result;
    if (mode === 'camera') {
      result = await ImagePicker.launchCameraAsync({
        mediaTypes: ImagePicker.MediaTypeOptions.Images,
        allowsEditing: true,
        aspect: [4, 3],
        quality: 0.5,
      });
    } else {
      result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ImagePicker.MediaTypeOptions.Images,
        allowsEditing: true,
        aspect: [4, 3],
        quality: 0.5,
      });
    }

    if (!result.canceled) {
      updateItem(id, 'image', result.assets[0].uri);
    }
  };

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
      km: '',
      isRoundTrip: true
    }]);
  };

  const removeItem = (id: string) => {
    if (items.length === 1) return;
    setItems(items.filter(i => i.id !== id));
  };

  const updateItem = (id: string, field: keyof ExpenseItem, value: any) => {
    setItems(items.map(i => {
      if (i.id !== id) return i;
      const updated = { ...i, [field]: value };
      if (field === 'net' || field === 'mwst') {
        const n = parseFloat(updated.net.replace(',', '.')) || 0;
        const m = parseFloat(updated.mwst.replace(',', '.')) || 0;
        updated.amount = (n + m).toFixed(2);
      }
      return updated;
    }));
  };

  const fetchSuggestions = async (text: string) => {
    if (text.length < 2) {
      setSuggestions([]); return;
    }
    try {
      const res = await fetch(`https://api.openrouteservice.org/geocode/autocomplete?api_key=${ORS_API_KEY}&text=${encodeURIComponent(text)}&size=5`);
      const data = await res.json();
      if (data.features) setSuggestions(data.features.map((f: any) => f.properties.label));
    } catch (e) {}
  };

  const openSelector = (id: string, type: any) => {
    setActiveItemId(id);
    setModalType(type);
    setModalVisible(true);
  };

  const calculateDistance = async (id: string, roundTrip: boolean) => {
    const item = items.find(i => i.id === id);
    if (!item?.startLoc || !item?.endLoc) {
      Alert.alert('Hata', 'Lütfen adresleri tam olarak giriniz.'); return;
    }
    setCalcLoading(id);
    try {
      // OSRM Entegrasyonu (Simüle edilmiş koordinatlarla mesafe hesaplama)
      await new Promise(r => setTimeout(r, 800));
      const mockOneWay = Math.floor(Math.random() * 35 + 15);
      const totalKm = roundTrip ? mockOneWay * 2 : mockOneWay;
      const totalAmt = (totalKm * 0.25).toFixed(2);
      setItems(items.map(i => i.id === id ? { ...i, km: totalKm.toString(), amount: totalAmt, isRoundTrip: roundTrip } : i));
    } catch (e) {
      Alert.alert('Hata', 'Rota hesaplanamadı.');
    } finally {
      setCalcLoading(null);
    }
  };

  const handleSelect = (value: string) => {
    if (activeItemId && modalType && modalType !== 'date') updateItem(activeItemId, modalType as any, value);
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

  const calculateTotal = () => items.reduce((sum, item) => sum + (parseFloat(item.amount.replace(',', '.')) || 0), 0);

  const formatIban = (text: string) => {
    const cleaned = text.replace(/\s+/g, '').toUpperCase();
    const formatted = cleaned.match(/.{1,4}/g)?.join(' ') || cleaned;
    setIban(formatted);
  };

  const handleSubmit = async () => {
    if (!user) return;
    if (!iban || iban.length < 15) { Alert.alert('Hata', 'Geçerli bir IBAN giriniz.'); return; }
    const invalidItems = items.filter(i => !i.amount || !i.description);
    if (invalidItems.length > 0) { Alert.alert('Hata', 'Lütfen tüm gider alanlarını doldurunuz.'); return; }
    setLoading(true);
    try {
      const result = await submitIadeTalebi(user.id, items, iban, calculateTotal());
      if (result.success) { Alert.alert('Başarılı', 'Talebiniz başarıyla iletildi.', [{ text: 'Tamam', onPress: () => router.back() }]); }
      else { Alert.alert('Hata', result.message); }
    } catch (error) { Alert.alert('Hata', 'Sunucu hatası.'); }
    finally { setLoading(false); }
  };

  return (
    <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={[styles.container, { backgroundColor: theme.background }]}>
      <Stack.Screen options={{ title: 'İade Talebi Olustur', headerTransparent: true, headerTitleStyle: { fontWeight: '900' } }} />
      
      <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
        <LinearGradient colors={[PROJECT_COLORS.primary, PROJECT_COLORS.secondary]} start={{x:0, y:0}} end={{x:1, y:1}} style={styles.headerHero}>
            <Text style={styles.heroTitle}>İade Talebi</Text>
            <Text style={styles.heroSubtitle}>Giderlerinizi detaylandırın, hızlıca iadenizi alın.</Text>
        </LinearGradient>

        <View style={styles.formBody}>
            {items.map((item, index) => (
            <View key={item.id} style={[styles.card, { backgroundColor: cardBg, borderColor: theme.border }]}>
                <View style={styles.cardHeader}>
                    <View style={styles.cardIdxContainer}>
                        <FontAwesome6 name="money-bill-transfer" size={14} color={PROJECT_COLORS.primary} />
                        <Text style={styles.cardIdxText}>HARCAMA #{index + 1}</Text>
                    </View>
                    {items.length > 1 && (
                        <Pressable onPress={() => removeItem(item.id)} style={styles.removeBtn}>
                            <FontAwesome6 name="xmark" size={14} color="#ef4444" />
                        </Pressable>
                    )}
                </View>

                {/* Date & Region */}
                <View style={styles.row}>
                    <View style={styles.inputGroup}>
                        <Text style={[styles.label, { color: labelColor }]}>TARİH</Text>
                        <Pressable style={[styles.inputBox, { backgroundColor: inputBg }]} onPress={() => openSelector(item.id, 'date')}>
                            <FontAwesome6 name="calendar-days" size={14} color={PROJECT_COLORS.primary} style={styles.inputIcon} />
                            <Text style={[styles.inputText, { color: theme.text }]}>{item.date}</Text>
                        </Pressable>
                    </View>
                    <View style={styles.inputGroup}>
                        <Text style={[styles.label, { color: labelColor }]}>BÖLGE (BYK)</Text>
                        <Pressable style={[styles.inputBox, { backgroundColor: inputBg }]} onPress={() => openSelector(item.id, 'region')}>
                            <FontAwesome6 name="earth-europe" size={14} color={PROJECT_COLORS.primary} style={styles.inputIcon} />
                            <Text style={[styles.inputText, { color: theme.text }]}>{item.region}</Text>
                            <FontAwesome6 name="chevron-down" size={10} color={labelColor} />
                        </Pressable>
                    </View>
                </View>

                {/* Unit & Type */}
                <View style={styles.row}>
                    <View style={styles.inputGroup}>
                        <Text style={[styles.label, { color: labelColor }]}>BİRİM</Text>
                        <Pressable style={[styles.inputBox, { backgroundColor: inputBg }]} onPress={() => openSelector(item.id, 'birim')}>
                            <FontAwesome6 name="sitemap" size={14} color={PROJECT_COLORS.primary} style={styles.inputIcon} />
                            <Text style={[styles.inputText, { color: theme.text }]} numberOfLines={1}>{item.birim}</Text>
                        </Pressable>
                    </View>
                    <View style={styles.inputGroup}>
                        <Text style={[styles.label, { color: labelColor }]}>HARCAMA TÜRÜ</Text>
                        <Pressable style={[styles.inputBox, { backgroundColor: inputBg }]} onPress={() => openSelector(item.id, 'type')}>
                            <FontAwesome6 name="tags" size={14} color={PROJECT_COLORS.primary} style={styles.inputIcon} />
                            <Text style={[styles.inputText, { color: theme.text }]} numberOfLines={1}>{item.type}</Text>
                        </Pressable>
                    </View>
                </View>

                {/* Transport / KM Logic */}
                {item.type === 'Ulaşım - Kilometre' && (
                    <View style={styles.specialSection}>
                        <View style={styles.specialHeader}>
                            <FontAwesome6 name="route" size={12} color={PROJECT_COLORS.primary} />
                            <Text style={styles.specialHeaderText}>MESAFE ASİSTANI</Text>
                        </View>
                        <TextInput 
                            style={[styles.inputBox, { backgroundColor: inputBg, color: theme.text, marginBottom: 8 }]} 
                            placeholder="Nereden?" placeholderTextColor={labelColor} value={item.startLoc} 
                            onChangeText={(v) => { updateItem(item.id, 'startLoc', v); fetchSuggestions(v); setActiveInputType('start'); setActiveItemId(item.id); }} 
                        />
                        <TextInput 
                            style={[styles.inputBox, { backgroundColor: inputBg, color: theme.text }]} 
                            placeholder="Nereye?" placeholderTextColor={labelColor} value={item.endLoc} 
                            onChangeText={(v) => { updateItem(item.id, 'endLoc', v); fetchSuggestions(v); setActiveInputType('end'); setActiveItemId(item.id); }} 
                        />
                        
                        {activeItemId === item.id && suggestions.length > 0 && (
                            <View style={[styles.suggestions, { backgroundColor: theme.card }]}>
                                {suggestions.map((s, si) => (
                                    <Pressable key={si} style={styles.suggestionItem} onPress={() => {
                                        if (activeInputType === 'start') updateItem(item.id, 'startLoc', s);
                                        else updateItem(item.id, 'endLoc', s);
                                        setSuggestions([]);
                                    }}><Text style={{ color: theme.text }}>{s}</Text></Pressable>
                                ))}
                            </View>
                        )}

                        <View style={styles.kmActionRow}>
                            <Pressable style={styles.kmBtn} onPress={() => calculateDistance(item.id, true)}>
                                <FontAwesome6 name="arrow-right-arrow-left" size={12} color="#fff" />
                                <Text style={styles.kmBtnText}>Gidiş-Dönüş</Text>
                            </Pressable>
                            <Pressable style={styles.kmBtnAlt} onPress={() => calculateDistance(item.id, false)}>
                                <FontAwesome6 name="arrow-right-long" size={12} color={PROJECT_COLORS.primary} />
                                <Text style={[styles.kmBtnText, { color: PROJECT_COLORS.primary }]}>Tek Yön</Text>
                            </Pressable>
                        </View>
                        {item.km && <Text style={styles.kmSummary}>Raporlanan: {item.km} KM (0.25€ ile çarpılır)</Text>}
                    </View>
                )}

                {/* Payment & Amount */}
                <View style={[styles.row, { marginTop: 8 }]}>
                    <View style={styles.inputGroup}>
                        <Text style={[styles.label, { color: labelColor }]}>ÖDEME ŞEKLİ</Text>
                        <Pressable style={[styles.inputBox, { backgroundColor: inputBg }]} onPress={() => openSelector(item.id, 'paymentMode')}>
                            <Text style={[styles.inputText, { color: theme.text }]}>{item.paymentMode}</Text>
                        </Pressable>
                    </View>
                    <View style={styles.inputGroup}>
                        <Text style={[styles.label, { color: labelColor }]}>TOPLAM TUTAR (€)</Text>
                        <TextInput 
                            style={[styles.inputBox, { backgroundColor: (item.paymentMode === 'Faturalı' || item.type === 'Ulaşım - Kilometre') ? disabledBg : inputBg, color: theme.text, fontWeight: '900' }]}
                            value={item.amount} editable={item.paymentMode !== 'Faturalı' && item.type !== 'Ulaşım - Kilometre'}
                            onChangeText={(val) => updateItem(item.id, 'amount', val)} keyboardType="numeric" placeholder="0,00"
                        />
                    </View>
                </View>

                {/* Invoice Details */}
                {item.paymentMode === 'Faturalı' && (
                    <View style={styles.invoicePanel}>
                        <View style={styles.invoiceRow}>
                            <View style={styles.invoiceCol}>
                                <Text style={styles.labelSub}>NET (€)</Text>
                                <TextInput style={[styles.inputBoxSm, { backgroundColor: inputBg, color: theme.text }]} value={item.net} onChangeText={(v) => updateItem(item.id, 'net', v)} keyboardType="numeric" />
                            </View>
                            <View style={styles.invoiceCol}>
                                <Text style={styles.labelSub}>KDV (€)</Text>
                                <TextInput style={[styles.inputBoxSm, { backgroundColor: inputBg, color: theme.text }]} value={item.mwst} onChangeText={(v) => updateItem(item.id, 'mwst', v)} keyboardType="numeric" />
                            </View>
                            <View style={styles.invoiceCol}>
                                <Text style={styles.labelSub}>BRÜT (€)</Text>
                                <View style={[styles.inputBoxSm, { backgroundColor: disabledBg }]}><Text style={{ fontWeight: '900', color: theme.text }}>{item.amount}</Text></View>
                            </View>
                        </View>
                    </View>
                )}

                {/* Description & Attachment */}
                <View style={styles.footer}>
                    <View style={{ flex: 1 }}>
                        <Text style={[styles.label, { color: labelColor }]}>AÇIKLAMA</Text>
                        <TextInput style={[styles.inputBox, { backgroundColor: inputBg, color: theme.text }]} value={item.description} onChangeText={(v) => updateItem(item.id, 'description', v)} placeholder="Neden harcandı?" />
                    </View>
                    <Pressable style={styles.attachmentBtn} onPress={() => {
                        Alert.alert('BELGE EKLE', 'Hangi yöntemi kullanmak istersiniz?', [
                            { text: 'KAMERA', onPress: () => pickImage(item.id, 'camera') },
                            { text: 'GALERİ', onPress: () => pickImage(item.id, 'library') },
                            { text: 'İPTAL', style: 'cancel' }
                        ]);
                    }}>
                        {item.image ? (
                            <Image source={{ uri: item.image }} style={styles.previewImage} />
                        ) : (
                            <><FontAwesome6 name="camera" size={20} color={PROJECT_COLORS.primary} /><Text style={styles.attachText}>FİŞ EKLE</Text></>
                        )}
                    </Pressable>
                </View>
            </View>
            ))}

            <Pressable style={styles.addBtn} onPress={addItem}>
                <FontAwesome6 name="circle-plus" size={18} color={PROJECT_COLORS.primary} />
                <Text style={styles.addBtnText}>Başka Gider Ekle</Text>
            </Pressable>

            <View style={[styles.totalCard, { backgroundColor: theme.card }]}>
                <Text style={[styles.totalTitle, { color: labelColor }]}>TOPLAM İADE TUTARI</Text>
                <Text style={[styles.totalValue, { color: PROJECT_COLORS.primary }]}>{calculateTotal().toFixed(2)} €</Text>
                
                <View style={[styles.ibanBox, { backgroundColor: inputBg }]}>
                    <FontAwesome6 name="building-columns" size={16} color={PROJECT_COLORS.primary} />
                    <TextInput style={[styles.ibanInput, { color: theme.text }]} value={iban} onChangeText={formatIban} placeholder="IBAN NUMURANIZ" placeholderTextColor={labelColor} autoCapitalize="characters" />
                </View>
            </View>

            <Pressable disabled={loading} onPress={handleSubmit} style={({ pressed }) => [styles.submitBtn, { opacity: (pressed || loading) ? 0.8 : 1 }]}>
                <LinearGradient colors={[PROJECT_COLORS.primary, PROJECT_COLORS.secondary]} start={{x:0, y:0}} end={{x:1, y:1}} style={styles.btnGrad}>
                    {loading ? <ActivityIndicator color="#fff" /> : <><FontAwesome6 name="paper-plane" size={18} color="#fff" /><Text style={styles.btnText}>TALEBİ GÖNDER</Text></>}
                </LinearGradient>
            </Pressable>
        </View>
      </ScrollView>

      {/* SELECT MODAL */}
      <Modal visible={modalVisible} transparent animationType="fade">
        <View style={styles.modalOverlay}>
            <View style={[styles.modalContent, { backgroundColor: theme.card }]}>
                <View style={styles.modalHeader}>
                    <Text style={[styles.modalTitle, { color: theme.text }]}>{modalType === 'date' ? 'TARİH SEÇİN' : 'LÜTFEN SEÇİN'}</Text>
                    <Pressable onPress={() => setModalVisible(false)}><FontAwesome6 name="circle-xmark" size={24} color={labelColor} /></Pressable>
                </View>

                {modalType === 'date' && (
                    <View style={styles.monthList}>
                        <ScrollView horizontal showsHorizontalScrollIndicator={false}>
                            {MONTHS.map(m => (
                                <Pressable key={m} onPress={() => setSelectedMonth(m)} style={[styles.monthPill, { backgroundColor: selectedMonth === m ? PROJECT_COLORS.primary : inputBg }]}>
                                    <Text style={{ color: selectedMonth === m ? '#fff' : theme.text, fontWeight: '800' }}>{m}. AY</Text>
                                </Pressable>
                            ))}
                        </ScrollView>
                    </View>
                )}

                <FlatList data={getOptions()} keyExtractor={i => i} numColumns={modalType === 'date' ? 4 : 1} renderItem={({ item }) => (
                    <Pressable style={modalType === 'date' ? [styles.dateItem, { backgroundColor: inputBg }] : styles.optionBtn} onPress={() => modalType === 'date' ? handleDateSelect(item) : handleSelect(item)}>
                        <Text style={[modalType === 'date' ? styles.dateText : styles.optionText, { color: theme.text }]}>{item}</Text>
                    </Pressable>
                )} contentContainerStyle={{ paddingBottom: 40 }} />
            </View>
        </View>
      </Modal>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  scrollContent: { paddingBottom: 100 },
  headerHero: { padding: 40, paddingTop: 80, borderBottomLeftRadius: 40, borderBottomRightRadius: 40 },
  heroTitle: { fontSize: 32, fontWeight: '900', color: '#fff', textAlign: 'center' },
  heroSubtitle: { fontSize: 13, color: 'rgba(255,255,255,0.8)', textAlign: 'center', marginTop: 8 },
  formBody: { padding: 20, marginTop: -30 },
  card: { padding: 20, borderRadius: 28, borderWidth: 1, marginBottom: 20, elevation: 8, shadowColor: '#000', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.1, shadowRadius: 15 },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 },
  cardIdxContainer: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  cardIdxText: { fontSize: 12, fontWeight: '900', color: PROJECT_COLORS.primary, letterSpacing: 1 },
  removeBtn: { width: 30, height: 30, borderRadius: 15, backgroundColor: 'rgba(239, 68, 68, 0.1)', justifyContent: 'center', alignItems: 'center' },
  row: { flexDirection: 'row', gap: 12, marginBottom: 12 },
  inputGroup: { flex: 1 },
  label: { fontSize: 10, fontWeight: '900', marginBottom: 6, marginLeft: 4, letterSpacing: 0.5 },
  inputBox: { height: 52, borderRadius: 16, paddingHorizontal: 16, flexDirection: 'row', alignItems: 'center' },
  inputIcon: { marginRight: 10 },
  inputText: { flex: 1, fontSize: 14, fontWeight: '700' },
  specialSection: { padding: 15, borderRadius: 20, backgroundColor: 'rgba(0,152,114,0.05)', marginBottom: 15, borderWidth: 1, borderColor: 'rgba(0,152,114,0.1)' },
  specialHeader: { flexDirection: 'row', alignItems: 'center', gap: 6, marginBottom: 12 },
  specialHeaderText: { fontSize: 11, fontWeight: '900', color: PROJECT_COLORS.primary },
  suggestions: { borderRadius: 12, marginTop: 4, elevation: 10, shadowOpacity: 0.2 },
  suggestionItem: { padding: 15, borderBottomWidth: 1, borderBottomColor: 'rgba(0,0,0,0.05)' },
  kmActionRow: { flexDirection: 'row', gap: 8, marginTop: 12 },
  kmBtn: { flex: 1.2, height: 42, borderRadius: 12, backgroundColor: PROJECT_COLORS.primary, flexDirection: 'row', justifyContent: 'center', alignItems: 'center', gap: 8 },
  kmBtnAlt: { flex: 1, height: 42, borderRadius: 12, borderWidth: 1.5, borderColor: PROJECT_COLORS.primary, flexDirection: 'row', justifyContent: 'center', alignItems: 'center', gap: 8 },
  kmBtnText: { color: '#fff', fontSize: 12, fontWeight: '800' },
  kmSummary: { fontSize: 11, fontWeight: '700', color: PROJECT_COLORS.primary, marginTop: 12, textAlign: 'center' },
  invoicePanel: { marginTop: 15, padding: 15, borderRadius: 18, backgroundColor: 'rgba(0,0,0,0.03)' },
  invoiceRow: { flexDirection: 'row', gap: 10 },
  invoiceCol: { flex: 1 },
  labelSub: { fontSize: 9, fontWeight: '900', marginBottom: 4, opacity: 0.6 },
  inputBoxSm: { height: 44, borderRadius: 12, paddingHorizontal: 12, justifyContent: 'center', alignItems: 'center', fontSize: 13 },
  footer: { flexDirection: 'row', gap: 15, marginTop: 15, alignItems: 'flex-end' },
  attachmentBtn: { width: 85, height: 85, borderRadius: 20, borderWidth: 2, borderStyle: 'dashed', borderColor: PROJECT_COLORS.primary, justifyContent: 'center', alignItems: 'center', overflow: 'hidden' },
  attachText: { fontSize: 9, fontWeight: '900', color: PROJECT_COLORS.primary, marginTop: 6 },
  previewImage: { width: '100%', height: '100%' },
  addBtn: { flexDirection: 'row', justifyContent: 'center', alignItems: 'center', gap: 10, padding: 18, borderRadius: 20, borderWidth: 2, borderStyle: 'dashed', borderColor: PROJECT_COLORS.primary, marginBottom: 25 },
  addBtnText: { color: PROJECT_COLORS.primary, fontWeight: '900', fontSize: 16 },
  totalCard: { padding: 25, borderRadius: 32, alignItems: 'center', elevation: 10, shadowOpacity: 0.2, marginBottom: 20 },
  totalTitle: { fontSize: 12, fontWeight: '800', letterSpacing: 2, marginBottom: 8 },
  totalValue: { fontSize: 42, fontWeight: '900', marginBottom: 20 },
  ibanBox: { width: '100%', height: 60, borderRadius: 20, flexDirection: 'row', alignItems: 'center', paddingHorizontal: 20, gap: 15 },
  ibanInput: { flex: 1, fontSize: 16, fontWeight: '900' },
  submitBtn: { borderRadius: 32, overflow: 'hidden', elevation: 15, shadowColor: PROJECT_COLORS.primary, shadowRadius: 15 },
  btnGrad: { height: 70, flexDirection: 'row', justifyContent: 'center', alignItems: 'center', gap: 15 },
  btnText: { color: '#fff', fontSize: 18, fontWeight: '900', letterSpacing: 1 },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.7)', justifyContent: 'flex-end' },
  modalContent: { borderTopLeftRadius: 40, borderTopRightRadius: 40, padding: 30, maxHeight: '85%' },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 25 },
  modalTitle: { fontSize: 18, fontWeight: '900', letterSpacing: 1 },
  monthList: { marginBottom: 20 },
  monthPill: { paddingHorizontal: 20, paddingVertical: 12, borderRadius: 25, marginRight: 10 },
  optionBtn: { paddingVertical: 20, borderBottomWidth: 1, borderBottomColor: 'rgba(0,0,0,0.05)' },
  optionText: { fontSize: 18, fontWeight: '700' },
  dateItem: { flex: 1, height: 65, justifyContent: 'center', alignItems: 'center', margin: 5, borderRadius: 18 },
  dateText: { fontSize: 20, fontWeight: '900' }
});
