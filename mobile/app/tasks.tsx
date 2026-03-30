import React, { useEffect, useState } from 'react';
import { StyleSheet, FlatList, ActivityIndicator, Pressable, RefreshControl } from 'react-native';
import { Stack, useLocalSearchParams } from 'expo-router';
import { SymbolView } from 'expo-symbols';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import { fetchTasks } from '@/services/api';

export default function TasksScreen() {
  const { type } = useLocalSearchParams<{ type: string }>();
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  const [tasks, setTasks] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const loadTasks = async () => {
    setLoading(true);
    const result = await fetchTasks(type);
    if (result.success) setTasks(result.tasks);
    setLoading(false);
  };

  useEffect(() => { loadTasks(); }, [type]);

  const title = type === 'izin' ? 'İzin Talepleri' : 'Harcama Talepleri';

  return (
    <View style={[styles.container, { backgroundColor: theme.background }]}>
      <Stack.Screen options={{ title }} />
      {loading && !refreshing ? (
        <View style={styles.center}><ActivityIndicator size="large" color={theme.tint} /></View>
      ) : (
        <FlatList
          data={tasks}
          keyExtractor={(item) => (item.izin_id || item.harcama_id).toString()}
          renderItem={({ item }) => (
            <Pressable style={[styles.card, { backgroundColor: theme.card, borderColor: theme.border }]}>
              <View style={styles.cardHeader}>
                <Text style={styles.userName}>{item.ad_soyad}</Text>
                <View style={[styles.statusBadge, { backgroundColor: item.durum === 'onaylandi' ? '#10b981' : item.durum === 'beklemede' ? '#f59e0b' : '#ef4444' }]}>
                    <Text style={styles.statusText}>{item.durum.toUpperCase()}</Text>
                </View>
              </View>
              <Text style={styles.reason}>{item.sebep || item.aciklama}</Text>
              <View style={styles.cardFooter}>
                <SymbolView name={{ ios: 'calendar', android: 'event', web: 'event' } as any} tintColor={theme.tabIconDefault} size={14} />
                <Text style={styles.dateText}>{item.baslangic_tarihi || item.tarih} {item.bitis_tarihi ? `- ${item.bitis_tarihi}` : ''}</Text>
              </View>
            </Pressable>
          )}
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
});
