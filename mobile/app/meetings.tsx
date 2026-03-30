import React, { useEffect, useState } from 'react';
import { StyleSheet, FlatList, ActivityIndicator, Pressable, RefreshControl } from 'react-native';
import { Stack, router } from 'expo-router';
import { SymbolView } from 'expo-symbols';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import { fetchMeetings } from '@/services/api';

export default function MeetingsScreen() {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  const [meetings, setMeetings] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const loadMeetings = async () => {
    setLoading(true);
    const result = await fetchMeetings();
    if (result.success) setMeetings(result.meetings);
    setLoading(false);
  };

  const onRefresh = async () => {
    setRefreshing(true);
    const result = await fetchMeetings();
    if (result.success) setMeetings(result.meetings);
    setRefreshing(false);
  };

  useEffect(() => { loadMeetings(); }, []);

  return (
    <View style={[styles.container, { backgroundColor: theme.background }]}>
      <Stack.Screen options={{ title: 'Toplantı Yönetimi' }} />
      
      {loading && !refreshing ? (
        <View style={styles.center}><ActivityIndicator size="large" color={theme.tint} /></View>
      ) : (
        <FlatList
          data={meetings}
          keyExtractor={(item) => item.toplanti_id.toString()}
          renderItem={({ item }) => (
            <Pressable style={[styles.meetingCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
               <View style={[styles.dateBox, { backgroundColor: theme.tint + '15' }]}>
                <Text style={[styles.dayText, { color: theme.tint }]}>{new Date(item.tarih).getDate()}</Text>
                <Text style={[styles.monthText, { color: theme.tint }]}>
                    {new Date(item.tarih).toLocaleDateString('tr-TR', { month: 'short' })}
                </Text>
              </View>
              <View style={styles.meetingInfo}>
                <Text style={styles.meetingTitle}>{item.baslik}</Text>
                <View style={styles.meetingDetails}>
                    <SymbolView name={{ ios: 'clock.fill', android: 'schedule', web: 'schedule' } as any} tintColor={theme.tabIconDefault} size={12} />
                    <Text style={styles.detailText}>{item.saat}</Text>
                    <SymbolView name={{ ios: 'person.2.fill', android: 'group', web: 'group' } as any} tintColor={theme.tabIconDefault} size={12} style={{marginLeft: 10}} />
                    <Text style={styles.detailText}>{item.katilimci_sayisi || 0} Kişi</Text>
                </View>
                <View style={[styles.statusBadge, { backgroundColor: item.durum === 'tamamlandi' ? '#10b981' : '#f59e0b' }]}>
                    <Text style={styles.statusText}>{item.durum.toUpperCase()}</Text>
                </View>
              </View>
              <SymbolView name={{ ios: 'chevron.right', android: 'chevron_right', web: 'chevron_right' } as any} tintColor={theme.tabIconDefault} size={16} />
            </Pressable>
          )}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={theme.tint} />}
          contentContainerStyle={styles.listContent}
          ListEmptyComponent={<Text style={styles.emptyText}>Toplantı bulunamadı.</Text>}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  listContent: { padding: 16 },
  meetingCard: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    borderRadius: 15,
    marginBottom: 12,
    borderWidth: 1,
  },
  dateBox: {
    width: 60,
    height: 60,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 15,
  },
  dayText: { fontSize: 20, fontWeight: 'bold' },
  monthText: { fontSize: 12, textTransform: 'uppercase' },
  meetingInfo: { flex: 1, backgroundColor: 'transparent' },
  meetingTitle: { fontSize: 16, fontWeight: '700' },
  meetingDetails: { flexDirection: 'row', alignItems: 'center', marginTop: 5, backgroundColor: 'transparent' },
  detailText: { fontSize: 13, opacity: 0.6, marginLeft: 4, marginRight: 10 },
  statusBadge: { alignSelf: 'flex-start', paddingHorizontal: 8, paddingVertical: 2, borderRadius: 6, marginTop: 8 },
  statusText: { color: 'white', fontSize: 10, fontWeight: 'bold' },
  emptyText: { textAlign: 'center', marginTop: 50, opacity: 0.5 },
});
