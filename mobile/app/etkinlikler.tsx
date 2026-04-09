import React, { useEffect, useState } from 'react';
import { StyleSheet, FlatList, ActivityIndicator, RefreshControl, View as RNView } from 'react-native';
import { Stack } from 'expo-router';
import { FontAwesome6 } from '@expo/vector-icons';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import { fetchEtkinlikler } from '@/services/api';

export default function EtkinliklerScreen() {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  const [etkinlikler, setEtkinlikler] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const loadData = async () => {
    setLoading(true);
    const result = await fetchEtkinlikler();
    if (result.success) {
      setEtkinlikler(result.etkinlikler);
    }
    setLoading(false);
  };

  const onRefresh = async () => {
    setRefreshing(true);
    const result = await fetchEtkinlikler();
    if (result.success) {
      setEtkinlikler(result.etkinlikler);
    }
    setRefreshing(false);
  };

  useEffect(() => {
    loadData();
  }, []);

  const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('tr-TR', { day: 'numeric', month: 'long', year: 'numeric' });
  };

  return (
    <View style={[styles.container, { backgroundColor: theme.background }]}>
      <Stack.Screen options={{ title: 'Çalışma Takvimi' }} />
      {loading && !refreshing ? (
        <View style={styles.center}>
          <ActivityIndicator size="large" color="#009872" />
        </View>
      ) : (
        <FlatList
          data={etkinlikler}
          keyExtractor={(item) => item.etkinlik_id.toString()}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor="#009872" />}
          renderItem={({ item }) => (
            <View style={[styles.card, { backgroundColor: theme.card, borderColor: theme.border }]}>
              <View style={styles.dateBadge}>
                <Text style={styles.dateDay}>{new Date(item.baslangic_tarihi).getDate()}</Text>
                <Text style={styles.dateMonth}>
                  {new Date(item.baslangic_tarihi).toLocaleDateString('tr-TR', { month: 'short' }).toUpperCase()}
                </Text>
              </View>
              <View style={styles.cardContent}>
                <Text style={styles.title}>{item.baslik}</Text>
                <View style={styles.infoRow}>
                  <FontAwesome6 name="clock" size={12} color={theme.tabIconDefault} />
                  <Text style={styles.infoText}>{formatDate(item.baslangic_tarihi)}</Text>
                </View>
                {item.konum && (
                  <View style={styles.infoRow}>
                    <FontAwesome6 name="location-dot" size={12} color={theme.tabIconDefault} />
                    <Text style={styles.infoText}>{item.konum}</Text>
                  </View>
                <View style={[styles.infoRow, { marginTop: 8 }]}>
                    <View style={[styles.bykBadge, { backgroundColor: item.byk_renk || '#009872' }]}>
                        <Text style={styles.bykText}>{item.byk_adi}</Text>
                    </View>
                </View>
              </View>
            </View>
          )}
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <FontAwesome6 name="calendar-xmark" size={50} color={theme.tabIconDefault} />
              <Text style={styles.emptyText}>Yakın zamanda etkinlik bulunmuyor.</Text>
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
  card: { 
    flexDirection: 'row', 
    borderRadius: 20, 
    marginBottom: 15, 
    borderWidth: 1, 
    overflow: 'hidden',
    padding: 12,
    alignItems: 'center'
  },
  dateBadge: {
    width: 60,
    height: 60,
    backgroundColor: '#00987215',
    borderRadius: 15,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 15
  },
  dateDay: { fontSize: 20, fontWeight: '800', color: '#009872' },
  dateMonth: { fontSize: 10, fontWeight: '700', color: '#009872', marginTop: -2 },
  cardContent: { flex: 1, backgroundColor: 'transparent' },
  title: { fontSize: 16, fontWeight: '700', marginBottom: 6 },
  infoRow: { flexDirection: 'row', alignItems: 'center', marginTop: 4, backgroundColor: 'transparent' },
  infoText: { fontSize: 12, opacity: 0.6, marginLeft: 6 },
  bykBadge: { paddingHorizontal: 8, paddingVertical: 2, borderRadius: 6 },
  bykText: { color: 'white', fontSize: 10, fontWeight: '700' },
  emptyContainer: { alignItems: 'center', justifyContent: 'center', marginTop: 100, backgroundColor: 'transparent' },
  emptyText: { marginTop: 20, fontSize: 16, opacity: 0.5, textAlign: 'center' }
});
