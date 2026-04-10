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
  FlatList
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
};

export default function IadeTalebiScreen() {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  const [loading, setLoading] = useState(false);
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
    amount: ''
  }]);

  const [modalVisible, setModalVisible] = useState(false);
  const [modalType, setModalType] = useState<'region' | 'birim' | 'type' | 'paymentMode' | 'date' | null>(null);
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
      amount: ''
    }]);
  };

  const removeItem = (id: string) => {
    if (items.length === 1) return;
    setItems(items.filter(i => i.id !== id));
  };

  const updateItem = (id: string, field: keyof ExpenseItem, value: string) => {
    setItems(items.map(i => i.id === id ? { ...i, [field]: value } : i));
  };

  const openSelector = (id: string, type: any) => {
    setActiveItemId(id);
    setModalType(type);
    setModalVisible(true);
  };

  const handleSelect = (value: string) => {
    if (activeItemId && modalType && modalType !== 'date') {
      updateItem(activeItemId, modalType, value);
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
      <Stack.Screen options={{ title: 'İade Talebi Formu' }} />
      
      <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
        <View style={styles.header}>
          <Text style={styles.headerTitle}>Yeni Gider Formu</Text>
          <Text style={styles.headerSubtitle}>Tüm harcamalarınızı kalem kalem ekleyebilirsiniz.</Text>
        </View>

        {items.map((item, index) => (
          <View key={item.id} style={[styles.card, { backgroundColor: theme.card, borderColor: theme.border }]}>
            <View style={styles.cardHeader}>
              <Text style={styles.cardIdx}>HARCAMA #{index + 1}</Text>
              {items.length > 1 && (
                <Pressable onPress={() => removeItem(item.id)}>
                  <FontAwesome6 name="trash-can" size={16} color="#ef4444" />
                </Pressable>
              )}
            </View>

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
                <Text style={[styles.label, { color: theme.text }]}>Ödeme Şekli</Text>
                <Pressable 
                    style={[styles.pickerFake, { backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f1f5f9' }]}
                    onPress={() => openSelector(item.id, 'paymentMode')}
                >
                    <Text style={{ color: theme.text, fontWeight: '600' }}>{item.paymentMode}</Text>
                    <FontAwesome6 name="chevron-down" size={10} color="#94a3b8" />
                </Pressable>
              </View>
            </View>

            <View style={styles.row}>
                <View style={styles.inputGroupHalf}>
                    <Text style={[styles.label, { color: theme.text }]}>Harcama Türü</Text>
                    <Pressable 
                        style={[styles.pickerFake, { backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f1f5f9' }]}
                        onPress={() => openSelector(item.id, 'type')}
                    >
                        <Text style={{ color: theme.text, fontWeight: '600' }} numberOfLines={1}>{item.type}</Text>
                        <FontAwesome6 name="chevron-down" size={10} color="#94a3b8" />
                    </Pressable>
                </View>
                <View style={styles.inputGroupHalf}>
                    <Text style={[styles.label, { color: theme.text }]}>Tutar (€)</Text>
                    <TextInput 
                    style={[styles.input, { backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f1f5f9', color: theme.text }]}
                    value={item.amount}
                    onChangeText={(val) => updateItem(item.id, 'amount', val)}
                    keyboardType="numeric"
                    placeholder="0,00"
                    placeholderTextColor="#94a3b8"
                    />
                </View>
            </View>

            <View style={styles.inputGroupFull}>
              <Text style={[styles.label, { color: theme.text }]}>Açıklama</Text>
              <TextInput 
                style={[styles.input, { backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f1f5f9', color: theme.text }]}
                value={item.description}
                onChangeText={(val) => updateItem(item.id, 'description', val)}
                placeholder="Harcamanın sebebi..."
                placeholderTextColor="#94a3b8"
              />
            </View>
          </View>
        ))}

        <Pressable style={styles.addButton} onPress={addItem}>
          <FontAwesome6 name="plus" size={14} color={PROJECT_COLORS.primary} />
          <Text style={styles.addButtonText}>Yeni Kalem Ekle</Text>
        </Pressable>

        <View style={[styles.card, styles.ibanCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
          <Text style={[styles.label, { color: theme.text }]}>IBAN (Örn: AT00...)</Text>
          <View style={[styles.inputWrapper, { backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f1f5f9' }]}>
            <FontAwesome6 name="building-columns" size={16} color={theme.tabIconDefault} style={styles.inputIcon} />
            <TextInput 
              style={[styles.inputField, { color: theme.text }]}
              value={iban}
              onChangeText={formatIban}
              placeholder="IBAN numaranız"
              placeholderTextColor="#94a3b8"
              autoCapitalize="characters"
            />
          </View>
          <Text style={styles.ibanNotice}>Banka hesabı isim-soyisim ile uyuşmalıdır.</Text>
        </View>

        <View style={styles.totalRow}>
          <Text style={[styles.totalLabel, { color: theme.text }]}>Toplam:</Text>
          <Text style={[styles.totalValue, { color: PROJECT_COLORS.primary }]}>{calculateTotal().toFixed(2)} €</Text>
        </View>

        <Pressable 
          disabled={loading} 
          onPress={handleSubmit}
          style={({ pressed }) => [
            styles.submitButton,
            { backgroundColor: PROJECT_COLORS.primary, opacity: (pressed || loading) ? 0.8 : 1 }
          ]}
        >
          {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.submitButtonText}>Formu Gönder</Text>}
        </Pressable>
      </ScrollView>

      {/* GLOBAL SELECT MODAL */}
      <Modal visible={modalVisible} transparent={true} animationType="slide">
        <View style={styles.modalBg}>
            <View style={[styles.modalContent, { backgroundColor: theme.card }]}>
                <View style={styles.modalHeader}>
                    <Text style={[styles.modalTitle, { color: theme.text }]}>
                        {modalType === 'date' ? 'Tarih Seçin' : 'Lütfen Seçin'}
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
  scrollContent: { padding: 20, paddingBottom: 60 },
  header: { marginBottom: 24 },
  headerTitle: { fontSize: 24, fontWeight: '800', marginBottom: 4 },
  headerSubtitle: { fontSize: 14, color: '#64748b', lineHeight: 20 },
  card: { padding: 18, borderRadius: 24, borderWidth: 1, marginBottom: 16, elevation: 2, shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.05, shadowRadius: 10 },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 16, alignItems: 'center' },
  cardIdx: { fontSize: 11, fontWeight: '800', color: '#64748b', letterSpacing: 1 },
  row: { flexDirection: 'row', gap: 12, marginBottom: 12 },
  inputGroupFull: { flex: 1, marginBottom: 12 },
  inputGroupHalf: { flex: 0.5 },
  label: { fontSize: 12, fontWeight: '700', marginBottom: 6, marginLeft: 4 },
  input: { height: 48, borderRadius: 12, paddingHorizontal: 14, fontSize: 14, fontWeight: '500' },
  pickerFake: { height: 48, borderRadius: 12, paddingHorizontal: 14, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  addButton: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', padding: 16, borderRadius: 16, borderWidth: 1, borderStyle: 'dashed', borderColor: PROJECT_COLORS.primary, marginBottom: 24 },
  addButtonText: { marginLeft: 8, color: PROJECT_COLORS.primary, fontWeight: '700', fontSize: 14 },
  ibanCard: { marginBottom: 24 },
  inputWrapper: { flexDirection: 'row', alignItems: 'center', height: 56, borderRadius: 16, paddingHorizontal: 16 },
  inputIcon: { marginRight: 12 },
  inputField: { flex: 1, fontSize: 16, fontWeight: '600' },
  ibanNotice: { fontSize: 11, color: '#ef4444', marginTop: 8, textAlign: 'center', fontWeight: '500' },
  totalRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24, paddingHorizontal: 10 },
  totalLabel: { fontSize: 18, fontWeight: '700' },
  totalValue: { fontSize: 28, fontWeight: '900' },
  submitButton: { height: 60, borderRadius: 30, justifyContent: 'center', alignItems: 'center', elevation: 4, shadowColor: PROJECT_COLORS.primary, shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.3, shadowRadius: 10 },
  submitButtonText: { color: '#ffffff', fontSize: 18, fontWeight: '800' },
  modalBg: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
  modalContent: { borderTopLeftRadius: 30, borderTopRightRadius: 30, padding: 25, maxHeight: '80%' },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 },
  modalTitle: { fontSize: 18, fontWeight: '800' },
  monthSelector: { marginBottom: 15, paddingBottom: 5 },
  monthPill: { paddingHorizontal: 15, paddingVertical: 8, borderRadius: 20, marginRight: 8, borderWidth: 1, borderColor: '#e2e8f0' },
  optionItem: { paddingVertical: 18, borderBottomWidth: 1, borderBottomColor: '#f1f5f9' },
  optionText: { fontSize: 16, fontWeight: '500' },
  dateItem: { flex: 1, height: 50, justifyContent: 'center', alignItems: 'center', margin: 4, borderRadius: 12, backgroundColor: '#f1f5f9', borderMode: 'dark' ? '#1e293b' : '#f1f5f9' },
  dateText: { fontSize: 16, fontWeight: '700' }
});
