import React, { useState, useEffect } from 'react';
import { 
  StyleSheet, 
  ScrollView, 
  TextInput, 
  Pressable, 
  Alert, 
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform
} from 'react-native';
import { Stack, router } from 'expo-router';
import { FontAwesome6 } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { LinearGradient } from 'expo-linear-gradient';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import { submitIadeTalebi } from '@/services/api';

const PROJECT_COLORS = {
  primary: '#009872',
  secondary: '#004d3a',
  bgSoft: '#f8fafc',
};

type ExpenseItem = {
  id: string;
  date: string;
  region: string;
  type: string;
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
    type: 'Genel',
    description: '',
    amount: ''
  }]);

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
      type: 'Genel',
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

  const calculateTotal = () => {
    return items.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0);
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
      <Stack.Screen options={{ title: 'İade Talep Formu' }} />
      
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
              <View style={styles.inputGroupFull}>
                <Text style={[styles.label, { color: theme.text }]}>Tarih</Text>
                <TextInput 
                  style={[styles.input, { backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f1f5f9', color: theme.text }]}
                  value={item.date}
                  onChangeText={(val) => updateItem(item.id, 'date', val)}
                  placeholder="YYYY-MM-DD"
                  placeholderTextColor="#94a3b8"
                />
              </View>
            </View>

            <View style={styles.row}>
              <View style={styles.inputGroupHalf}>
                <Text style={[styles.label, { color: theme.text }]}>Birim</Text>
                <View style={[styles.pickerFake, { backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f1f5f9' }]}>
                  <Text style={{ color: theme.text }}>{item.region}</Text>
                </View>
              </View>
              <View style={styles.inputGroupHalf}>
                <Text style={[styles.label, { color: theme.text }]}>Tutar (€)</Text>
                <TextInput 
                  style={[styles.input, { backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f1f5f9', color: theme.text }]}
                  value={item.amount}
                  onChangeText={(val) => updateItem(item.id, 'amount', val)}
                  keyboardType="numeric"
                  placeholder="0.00"
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
                placeholder="Örn: X Toplantısı yol masrafı"
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
          <Text style={[styles.label, { color: theme.text }]}>IBAN (AT/TR..)</Text>
          <View style={[styles.inputWrapper, { backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f1f5f9' }]}>
            <FontAwesome6 name="building-columns" size={16} color={theme.tabIconDefault} style={styles.inputIcon} />
            <TextInput 
              style={[styles.inputField, { color: theme.text }]}
              value={iban}
              onChangeText={formatIban}
              placeholder="AT00 0000 0000 0000 0000"
              placeholderTextColor="#94a3b8"
              autoCapitalize="characters"
            />
          </View>
          <Text style={styles.ibanNotice}>İsim-Soyisim ve IBAN sahibi birebir uyuşmalıdır.</Text>
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
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  scrollContent: { padding: 20, paddingBottom: 60 },
  header: { marginBottom: 24 },
  headerTitle: { fontSize: 24, fontWeight: '800', marginBottom: 4 },
  headerSubtitle: { fontSize: 14, color: '#64748b', lineHeight: 20 },
  card: { padding: 20, borderRadius: 24, borderWidth: 1, marginBottom: 16, elevation: 2, shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.05, shadowRadius: 10 },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 16, alignItems: 'center' },
  cardIdx: { fontSize: 11, fontWeight: '800', color: '#64748b', letterSpacing: 1 },
  row: { flexDirection: 'row', gap: 12, marginBottom: 12 },
  inputGroupFull: { flex: 1, marginBottom: 12 },
  inputGroupHalf: { flex: 0.5 },
  label: { fontSize: 13, fontWeight: '700', marginBottom: 8, marginLeft: 4 },
  input: { height: 50, borderRadius: 14, paddingHorizontal: 16, fontSize: 15, fontWeight: '500' },
  pickerFake: { height: 50, borderRadius: 14, paddingHorizontal: 16, justifyContent: 'center' },
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
  submitButtonText: { color: '#ffffff', fontSize: 18, fontWeight: '800' }
});
