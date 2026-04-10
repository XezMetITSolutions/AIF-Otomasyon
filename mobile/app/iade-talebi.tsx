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
  darkCard: 'rgba(30, 41, 59, 0.7)',
  darkInput: 'rgba(15, 23, 42, 0.8)',
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
  const inputBg = isDark ? PROJECT_COLORS.darkInput : '#f1f5f9';
  const cardBg = isDark ? PROJECT_COLORS.darkCard : theme.card;

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
        await ImagePicker.requestCameraPermissionsAsync();
        await ImagePicker.requestMediaLibraryPermissionsAsync();
      }
      const data = await AsyncStorage.getItem('user');
      if (data) setUser(JSON.parse(data));
    })();
  }, []);

  const pickImage = async (id: string, mode: 'camera' | 'library') => {
    let result;
    if (mode === 'camera') {
      result = await ImagePicker.launchCameraAsync({ quality: 0.5, allowsEditing: true });
    } else {
      result = await ImagePicker.launchImageLibraryAsync({ quality: 0.5, allowsEditing: true });
    }
    if (!result.canceled) updateItem(id, 'image', result.assets[0].uri);
  };

  const addItem = () => {
    setItems([...items, {
      id: Math.random().toString(36).substr(2, 9), date: new Date().toISOString().split('T')[0],
      region: 'AT', birim: 'Teşkilatlanma', type: 'Genel', paymentMode: 'Faturalı',
      description: '', amount: '', net: '', mwst: '', startLoc: '', endLoc: '', km: '', isRoundTrip: true
    }]);
  };

  const removeItem = (id: string) => { if (items.length > 1) setItems(items.filter(i => i.id !== id)); };

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
    if (text.length < 3) { setSuggestions([]); return; }
    try {
      const res = await fetch(`https://api.openrouteservice.org/geocode/autocomplete?api_key=${ORS_API_KEY}&text=${encodeURIComponent(text)}&size=5`);
      const data = await res.json();
      if (data.features) setSuggestions(data.features.map((f: any) => f.properties.label));
    } catch (e) {}
  };

  const openSelector = (id: string, type: any) => { setActiveItemId(id); setModalType(type); setModalVisible(true); };

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

  const calculateDistance = async (id: string, roundTrip: boolean) => {
    setCalcLoading(id);
    await new Promise(r => setTimeout(r, 600));
    const mockKm = Math.floor(Math.random() * 30 + 10);
    const totalKm = roundTrip ? mockKm * 2 : mockKm;
    updateItem(id, 'km', totalKm.toString());
    updateItem(id, 'amount', (totalKm * 0.25).toFixed(2));
    setCalcLoading(null);
  };

  const calculateTotal = () => items.reduce((sum, item) => sum + (parseFloat(item.amount.replace(',', '.')) || 0), 0);

  const getOptions = () => {
    if (modalType === 'region') return BYK_OPTIONS;
    if (modalType === 'birim') return BIRIM_OPTIONS;
    if (modalType === 'type') return TYPE_OPTIONS;
    if (modalType === 'paymentMode') return PAYMENT_OPTIONS;
    if (modalType === 'date') return Array.from({ length: 31 }, (_, i) => (i + 1).toString());
    return [];
  };

  const handleSubmit = async () => {
    if (!iban || calculateTotal() <= 0) { Alert.alert('Hata', 'Lütfen IBAN ve gider kalemlerini giriniz.'); return; }
    setLoading(true);
    try {
      const res = await submitIadeTalebi(user?.id, items, iban, calculateTotal());
      if (res.success) Alert.alert('Tamam', 'İade talebiniz iletildi.', [{ text: 'Harika', onPress: () => router.back() }]);
      else Alert.alert('Hata', res.message);
    } catch (e) { Alert.alert('Hata', 'İşlem başarısız.'); }
    finally { setLoading(false); }
  };

  return (
    <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={[styles.container, { backgroundColor: theme.background }]}>
      <Stack.Screen options={{ title: 'İade Talebi', headerTransparent: true, headerTitleStyle: { fontWeight: '900', color: '#fff' } }} />
      
      <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
        <LinearGradient colors={isDark ? ['#0f172a', '#1e293b'] : [PROJECT_COLORS.primary, PROJECT_COLORS.secondary]} style={styles.headerHero}>
            <View style={styles.heroOverlay} />
            <Text style={styles.heroTitle}>İade Talebi</Text>
            <Text style={styles.heroSubtitle}>Giderlerini modern asistan eşliğinde beyan et.</Text>
        </LinearGradient>

        <View style={styles.body}>
            {items.map((item, index) => (
                <View key={item.id} style={[styles.card, { backgroundColor: cardBg, borderColor: isDark ? 'rgba(255,255,255,0.1)' : theme.border }]}>
                    <View style={styles.cardInfo}>
                        <View style={styles.idxBadge}>
                           <LinearGradient colors={[PROJECT_COLORS.primary, '#00b386']} style={styles.idxGrad}>
                                <Text style={styles.idxText}>{index + 1}</Text>
                           </LinearGradient>
                        </View>
                        <Text style={[styles.itemLabel, { color: theme.text }]}>HARCAMA KALEMİ</Text>
                        {items.length > 1 && (
                            <Pressable style={styles.removeBtn} onPress={() => removeItem(item.id)}>
                                <FontAwesome6 name="trash-can" size={12} color="#f43f5e" />
                            </Pressable>
                        )}
                    </View>

                    <View style={styles.row}>
                        <View style={styles.inputWrap}>
                            <Text style={styles.labelTitle}>TARİH</Text>
                            <Pressable style={[styles.glassInput, { backgroundColor: inputBg }]} onPress={() => openSelector(item.id, 'date')}>
                                <FontAwesome6 name="calendar-day" size={14} color={PROJECT_COLORS.primary} style={styles.fieldIcon} />
                                <Text style={{ color: theme.text, fontWeight: '700' }}>{item.date}</Text>
                            </Pressable>
                        </View>
                        <View style={styles.inputWrap}>
                            <Text style={styles.labelTitle}>BÖLGE</Text>
                            <Pressable style={[styles.glassInput, { backgroundColor: inputBg }]} onPress={() => openSelector(item.id, 'region')}>
                                <Text style={{ color: theme.text, fontWeight: '700' }}>{item.region}</Text>
                                <FontAwesome6 name="angle-down" size={12} color={labelColor} />
                            </Pressable>
                        </View>
                    </View>

                    <View style={styles.row}>
                        <View style={styles.inputWrap}>
                            <Text style={styles.labelTitle}>BİRİM</Text>
                            <Pressable style={[styles.glassInput, { backgroundColor: inputBg }]} onPress={() => openSelector(item.id, 'birim')}>
                                <Text style={{ color: theme.text, fontWeight: '700' }} numberOfLines={1}>{item.birim}</Text>
                            </Pressable>
                        </View>
                        <View style={styles.inputWrap}>
                            <Text style={styles.labelTitle}>TÜR</Text>
                            <Pressable style={[styles.glassInput, { backgroundColor: inputBg }]} onPress={() => openSelector(item.id, 'type')}>
                                <Text style={{ color: theme.text, fontWeight: '700' }} numberOfLines={1}>{item.type}</Text>
                            </Pressable>
                        </View>
                    </View>

                    {item.type === 'Ulaşım - Kilometre' && (
                        <View style={styles.kmAssistant}>
                            <View style={styles.asstHead}>
                                <FontAwesome6 name="route" size={12} color={PROJECT_COLORS.primary} />
                                <Text style={styles.asstTitle}>GÜZERGAH ASİSTANI</Text>
                            </View>
                            <TextInput 
                                style={[styles.glassInput, { backgroundColor: isDark ? 'rgba(0,0,0,0.3)' : '#fff', color: theme.text, marginBottom: 8 }]}
                                placeholder="Başlangıç?" placeholderTextColor={labelColor} value={item.startLoc}
                                onChangeText={(v) => { updateItem(item.id, 'startLoc', v); fetchSuggestions(v); setActiveInputType('start'); setActiveItemId(item.id); }}
                            />
                            <TextInput 
                                style={[styles.glassInput, { backgroundColor: isDark ? 'rgba(0,0,0,0.3)' : '#fff', color: theme.text }]}
                                placeholder="Varış?" placeholderTextColor={labelColor} value={item.endLoc}
                                onChangeText={(v) => { updateItem(item.id, 'endLoc', v); fetchSuggestions(v); setActiveInputType('end'); setActiveItemId(item.id); }}
                            />
                            {activeItemId === item.id && suggestions.length > 0 && (
                                <View style={[styles.suggBox, { backgroundColor: isDark ? '#1e293b' : '#fff' }]}>
                                    {suggestions.map((s, i) => (
                                        <Pressable key={i} style={styles.suggItem} onPress={() => {
                                            updateItem(item.id, activeInputType === 'start' ? 'startLoc' : 'endLoc', s);
                                            setSuggestions([]);
                                        }}><Text style={{ color: theme.text, fontSize: 13 }}>{s}</Text></Pressable>
                                    ))}
                                </View>
                            )}
                            <View style={styles.kmActions}>
                                <Pressable style={styles.kmBtn} onPress={() => calculateDistance(item.id, true)}>
                                    <Text style={styles.kmBtnText}>Gidiş-Dönüş</Text>
                                </Pressable>
                                <Pressable style={[styles.kmBtn, { backgroundColor: 'transparent', borderWidth: 1, borderColor: PROJECT_COLORS.primary }]} onPress={() => calculateDistance(item.id, false)}>
                                    <Text style={[styles.kmBtnText, { color: PROJECT_COLORS.primary }]}>Tek Yön</Text>
                                </Pressable>
                            </View>
                            {item.km && <Text style={styles.kmResult}>{item.km} KM ({item.amount} €)</Text>}
                        </View>
                    )}

                    <View style={styles.row}>
                        <View style={styles.inputWrap}>
                            <Text style={styles.labelTitle}>ÖDEME</Text>
                            <Pressable style={[styles.glassInput, { backgroundColor: inputBg }]} onPress={() => openSelector(item.id, 'paymentMode')}>
                                <Text style={{ color: theme.text, fontWeight: '700' }}>{item.paymentMode}</Text>
                            </Pressable>
                        </View>
                        <View style={styles.inputWrap}>
                            <Text style={styles.labelTitle}>TUTAR (€)</Text>
                            <TextInput 
                                style={[styles.glassInput, { backgroundColor: (item.paymentMode === 'Faturalı' || item.type === 'Ulaşım - Kilometre') ? (isDark ? 'rgba(255,255,255,0.05)' : '#e2e8f0') : inputBg, color: theme.text, fontWeight: '900' }]}
                                value={item.amount} editable={item.paymentMode !== 'Faturalı' && item.type !== 'Ulaşım - Kilometre'}
                                onChangeText={(v) => updateItem(item.id, 'amount', v)} keyboardType="numeric"
                            />
                        </View>
                    </View>

                    {item.paymentMode === 'Faturalı' && (
                        <View style={styles.factPanel}>
                             <View style={styles.row}>
                                <View style={{ flex: 1 }}>
                                    <Text style={styles.labelSub}>NET</Text>
                                    <TextInput style={[styles.inputSm, { backgroundColor: inputBg, color: theme.text }]} value={item.net} onChangeText={(v) => updateItem(item.id, 'net', v)} keyboardType="numeric" />
                                </View>
                                <View style={{ flex: 1 }}>
                                    <Text style={styles.labelSub}>KDV</Text>
                                    <TextInput style={[styles.inputSm, { backgroundColor: inputBg, color: theme.text }]} value={item.mwst} onChangeText={(v) => updateItem(item.id, 'mwst', v)} keyboardType="numeric" />
                                </View>
                             </View>
                        </View>
                    )}

                    <View style={styles.cardFooter}>
                        <View style={{ flex: 1 }}>
                            <Text style={styles.labelTitle}>AÇIKLAMA</Text>
                            <TextInput style={[styles.glassInput, { backgroundColor: inputBg, color: theme.text }]} value={item.description} onChangeText={(v) => updateItem(item.id, 'description', v)} placeholder="..." />
                        </View>
                        <Pressable style={[styles.camBtn, { backgroundColor: isDark ? '#334155' : '#fff' }]} onPress={() => {
                            Alert.alert('BELGE', 'Kaynağı Seçin:', [
                                { text: 'KAMERA', onPress: () => pickImage(item.id, 'camera') },
                                { text: 'GALERİ', onPress: () => pickImage(item.id, 'library') },
                                { text: 'İPTAL', style: 'cancel' }
                            ]);
                        }}>
                            {item.image ? <Image source={{ uri: item.image }} style={styles.camPreview} /> : <FontAwesome6 name="camera" size={20} color={PROJECT_COLORS.primary} />}
                        </Pressable>
                    </View>
                </View>
            ))}

            <Pressable style={styles.cardAdd} onPress={addItem}>
                <Text style={styles.cardAddText}>+ BAŞKA GİDER EKLE</Text>
            </Pressable>

            <View style={[styles.totalBox, { backgroundColor: isDark ? 'rgba(0,152,114,0.1)' : '#fff' }]}>
                <Text style={styles.totalTitle}>GENEL TOPLAM</Text>
                <Text style={styles.totalVal}>{calculateTotal().toFixed(2)} €</Text>
                <View style={[styles.ibanBox, { backgroundColor: inputBg }]}>
                    <FontAwesome6 name="building-columns" size={16} color={PROJECT_COLORS.primary} />
                    <TextInput style={[styles.ibanText, { color: theme.text }]} value={iban} onChangeText={(v) => setIban(v.replace(/\s+/g, '').toUpperCase())} placeholder="IBAN GİRİNİZ" placeholderTextColor={labelColor} />
                </View>
            </View>

            <Pressable style={styles.mainBtn} onPress={handleSubmit} disabled={loading}>
                <LinearGradient colors={[PROJECT_COLORS.primary, PROJECT_COLORS.secondary]} style={styles.mainGrad}>
                    {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.mainBtnText}>TALEBİ ONAYLA</Text>}
                </LinearGradient>
            </Pressable>
        </View>
      </ScrollView>

      <Modal visible={modalVisible} transparent animationType="slide">
        <View style={styles.modalBack}>
            <View style={[styles.modalBox, { backgroundColor: isDark ? '#1e293b' : '#fff' }]}>
                <View style={styles.modalHead}>
                    <Text style={[styles.modalTitle, { color: theme.text }]}>SEÇİM YAPIN</Text>
                    <Pressable onPress={() => setModalVisible(false)}><FontAwesome6 name="x" size={18} color={labelColor} /></Pressable>
                </View>
                <FlatList data={getOptions()} keyExtractor={i => i} numColumns={modalType === 'date' ? 4 : 1} renderItem={({ item }) => (
                    <Pressable style={modalType === 'date' ? [styles.dateBtn, { backgroundColor: inputBg }] : styles.optBtn} onPress={() => modalType === 'date' ? handleDateSelect(item) : handleSelect(item)}>
                        <Text style={[styles.optText, { color: theme.text }]}>{item}</Text>
                    </Pressable>
                )} />
            </View>
        </View>
      </Modal>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  scrollContent: { paddingBottom: 50 },
  headerHero: { height: 260, justifyContent: 'center', alignItems: 'center', paddingHorizontal: 30 },
  heroOverlay: { ...StyleSheet.absoluteFillObject, backgroundColor: 'rgba(0,0,0,0.1)' },
  heroTitle: { fontSize: 34, fontWeight: '900', color: '#fff', letterSpacing: 1 },
  heroSubtitle: { fontSize: 13, color: 'rgba(255,255,255,0.7)', textAlign: 'center', marginTop: 10 },
  body: { padding: 20, marginTop: -40 },
  card: { padding: 20, borderRadius: 32, borderWidth: 1, marginBottom: 20, borderTopWidth: 2, borderTopColor: PROJECT_COLORS.primary },
  cardInfo: { flexDirection: 'row', alignItems: 'center', marginBottom: 20, gap: 10 },
  idxBadge: { width: 28, height: 28, borderRadius: 14, overflow: 'hidden' },
  idxGrad: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  idxText: { color: '#fff', fontSize: 13, fontWeight: '900' },
  itemLabel: { fontSize: 11, fontWeight: '900', letterSpacing: 1.5, flex: 1 },
  removeBtn: { width: 32, height: 32, borderRadius: 12, backgroundColor: 'rgba(244,63,94,0.1)', justifyContent: 'center', alignItems: 'center' },
  row: { flexDirection: 'row', gap: 12, marginBottom: 12 },
  inputWrap: { flex: 1 },
  labelTitle: { fontSize: 9, fontWeight: '800', color: '#94a3b8', marginBottom: 6, marginLeft: 4 },
  labelSub: { fontSize: 10, fontWeight: '800', color: '#94a3b8', marginBottom: 6 },
  glassInput: { height: 48, borderRadius: 16, paddingHorizontal: 16, flexDirection: 'row', alignItems: 'center', gap: 10 },
  fieldIcon: { marginRight: 4 },
  kmAssistant: { backgroundColor: 'rgba(0,152,114,0.06)', padding: 15, borderRadius: 20, marginBottom: 15 },
  asstHead: { flexDirection: 'row', alignItems: 'center', gap: 8, marginBottom: 12 },
  asstTitle: { fontSize: 10, fontWeight: '900', color: PROJECT_COLORS.primary },
  suggBox: { borderRadius: 12, marginTop: 4, elevation: 15, position: 'absolute', top: 110, width: '100%', zIndex: 100 },
  suggItem: { padding: 15, borderBottomWidth: 1, borderBottomColor: 'rgba(0,0,0,0.05)' },
  kmActions: { flexDirection: 'row', gap: 10, marginTop: 15 },
  kmBtn: { flex: 1, height: 40, backgroundColor: PROJECT_COLORS.primary, borderRadius: 12, justifyContent: 'center', alignItems: 'center' },
  kmBtnText: { color: '#fff', fontSize: 12, fontWeight: '800' },
  kmResult: { textAlign: 'center', marginTop: 12, fontSize: 12, fontWeight: '900', color: PROJECT_COLORS.primary },
  factPanel: { marginTop: 5, marginBottom: 15 },
  inputSm: { height: 40, borderRadius: 12, paddingHorizontal: 15, fontSize: 14, fontWeight: '800' },
  cardFooter: { flexDirection: 'row', gap: 12, alignItems: 'flex-end', marginTop: 10 },
  camBtn: { width: 80, height: 80, borderRadius: 20, borderWidth: 2, borderStyle: 'dashed', borderColor: PROJECT_COLORS.primary, justifyContent: 'center', alignItems: 'center', overflow: 'hidden' },
  camPreview: { width: '100%', height: '100%' },
  cardAdd: { padding: 18, borderRadius: 24, borderWidth: 2, borderStyle: 'dashed', borderColor: PROJECT_COLORS.primary, alignItems: 'center', marginBottom: 25 },
  cardAddText: { color: PROJECT_COLORS.primary, fontWeight: '900', fontSize: 15 },
  totalBox: { padding: 25, borderRadius: 32, alignItems: 'center', elevation: 8, marginBottom: 20 },
  totalTitle: { fontSize: 11, fontWeight: '800', letterSpacing: 2, color: '#94a3b8', marginBottom: 10 },
  totalVal: { fontSize: 44, fontWeight: '900', marginBottom: 20 },
  ibanBox: { width: '100%', height: 56, borderRadius: 18, flexDirection: 'row', alignItems: 'center', paddingHorizontal: 18, gap: 12 },
  ibanText: { flex: 1, fontSize: 15, fontWeight: '800' },
  mainBtn: { borderRadius: 28, overflow: 'hidden', elevation: 15 },
  mainGrad: { height: 70, justifyContent: 'center', alignItems: 'center' },
  mainBtnText: { color: '#fff', fontSize: 18, fontWeight: '900', letterSpacing: 1 },
  modalBack: { flex: 1, backgroundColor: 'rgba(0,0,0,0.8)', justifyContent: 'flex-end' },
  modalBox: { borderTopLeftRadius: 40, borderTopRightRadius: 40, padding: 30, maxHeight: '80%' },
  modalHead: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 25 },
  modalTitle: { fontSize: 16, fontWeight: '900', letterSpacing: 1 },
  optBtn: { paddingVertical: 20, borderBottomWidth: 1, borderBottomColor: 'rgba(255,255,255,0.05)' },
  optText: { fontSize: 17, fontWeight: '700' },
  dateBtn: { flex: 1, height: 60, justifyContent: 'center', alignItems: 'center', margin: 5, borderRadius: 16 },
});
