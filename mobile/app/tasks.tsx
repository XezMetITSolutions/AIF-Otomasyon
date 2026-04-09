import React, { useEffect, useState } from 'react';
import { StyleSheet, FlatList, ActivityIndicator, Pressable, RefreshControl, Alert } from 'react-native';
import { Stack, useLocalSearchParams } from 'expo-router';
import { FontAwesome6 } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import { fetchTasks } from '@/services/api';

export default function TasksScreen() {
  const params = useLocalSearchParams<{ type: string; scope: string }>();
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  const [tasks, setTasks] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const loadTasks = async () => {
    setLoading(true);
    const userData = await AsyncStorage.getItem('user');
    const user = userData ? JSON.parse(userData) : null;
    
    const result = await fetchTasks(params.type, user?.id, params.scope);
    if (result.success) {
      setTasks(result.tasks);
    } else {
      Alert.alert('Hata', result.message || 'Veriler alınamadı.');
    }
    setLoading(false);
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await loadTasks();
    setRefreshing(false);
  };

  useEffect(() => { loadTasks(); }, [params.type, params.scope]);

  const title = params.type === 'izin' ? 'İzin Talepleri' : 
                params.type === 'harcama' ? 'Rezervasyon Talepleri' : 'Talepler';

  return (
    <View style={[styles.container, { backgroundColor: theme.background }]}>
      <Stack.Screen options={{ title }} />
      {loading && !refreshing ? (
        <View style={styles.center}><ActivityIndicator size="large" color={theme.tint} /></View>
      ) : (
        <FlatList
          data={tasks}
          keyExtractor={(item) => (item.izin_id || item.harcama_id || item.talep_id || Math.random()).toString()}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={theme.tint} />}
          renderItem={({ item }) => (
            <Pressable style={[styles.card, { backgroundColor: theme.card, borderColor: theme.border }]}>
              <View style={styles.cardHeader}>
                <Text style={styles.userName}>{item.ad_soyad || item.baslik}</Text>
                <View style={[styles.statusBadge, { backgroundColor: item.durum === 'onaylandi' ? '#10b981' : item.durum === 'beklemede' ? '#f59e0b' : '#ef4444' }]}>
                    <Text style={styles.statusText}>{item.durum.toUpperCase()}</Text>
                </View>
              </View>
              <Text style={styles.reason}>{item.sebep || item.aciklama || item.baslik}</Text>
              <View style={styles.cardFooter}>
                <FontAwesome6 name="calendar" color={theme.tabIconDefault} size={14} />
                <Text style={styles.dateText}>{item.baslangic_tarihi || item.tarih || item.olusturma_tarihi} {item.bitis_tarihi ? `- ${item.bitis_tarihi}` : ''}</Text>
              </View>
            </Pressable>
          )}
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <FontAwesome6 name="folder-open" size={50} color={theme.tabIconDefault} />
              <Text style={styles.emptyText}>Herhangi bir kayıt bulunamadı.</Text>
            </View>
          }
          contentContainerStyle={styles.listContent}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  listContent: { padding: 16 },
  card: { padding: 16, borderRadius: 15, marginBottom: 12, borderWidth: 1 },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', backgroundColor: 'transparent' },
  userName: { fontSize: 16, fontWeight: '700' },
  statusBadge: { paddingHorizontal: 8, paddingVertical: 2, borderRadius: 6 },
  statusText: { color: 'white', fontSize: 10, fontWeight: 'bold' },
  reason: { fontSize: 14, opacity: 0.7, marginTop: 8, lineHeight: 20 },
  cardFooter: { flexDirection: 'row', alignItems: 'center', marginTop: 12, backgroundColor: 'transparent' },
  dateText: { fontSize: 12, opacity: 0.5, marginLeft: 6 },
  emptyContainer: { alignItems: 'center', justifyContent: 'center', marginTop: 100, backgroundColor: 'transparent' },
  emptyText: { marginTop: 20, fontSize: 16, opacity: 0.5, textAlign: 'center' },
});
