import React, { useEffect, useState } from 'react';
import { StyleSheet, FlatList, ActivityIndicator, RefreshControl, Pressable } from 'react-native';
import { Stack } from 'expo-router';
import { FontAwesome6 } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import { fetchZiyaretler } from '@/services/api';

export default function SubeZiyaretleriScreen() {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  const [ziyaretler, setZiyaretler] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const loadData = async () => {
    setLoading(true);
    const userData = await AsyncStorage.getItem('user');
    const user = userData ? JSON.parse(userData) : null;
    
    const result = await fetchZiyaretler(user?.id);
    if (result.success) {
      setZiyaretler(result.ziyaretler);
    }
    setLoading(false);
  };

  const onRefresh = async () => {
    setRefreshing(true);
    const userData = await AsyncStorage.getItem('user');
    const user = userData ? JSON.parse(userData) : null;
    const result = await fetchZiyaretler(user?.id);
    if (result.success) {
      setZiyaretler(result.ziyaretler);
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
      <Stack.Screen options={{ title: 'Şube Ziyaretleri' }} />
      {loading && !refreshing ? (
        <View style={styles.center}>
          <ActivityIndicator size="large" color="#06b6d4" />
        </View>
      ) : (
        <FlatList
          data={ziyaretler}
          keyExtractor={(item) => item.ziyaret_id.toString()}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor="#06b6d4" />}
          renderItem={({ item }) => (
            <Pressable style={[styles.card, { backgroundColor: theme.card, borderColor: theme.border }]}>
              <View style={styles.cardHeader}>
                <View style={[styles.typeIcon, { backgroundColor: '#06b6d415' }]}>
                  <FontAwesome6 name="building-user" size={18} color="#06b6d4" />
                </View>
                <View style={styles.headerContent}>
                  <Text style={styles.subeName}>{item.byk_adi || 'Bilinmeyen Şube'}</Text>
                  <Text style={styles.grupName}>{item.grup_adi || 'Genel Grup'}</Text>
                </View>
                <View style={[styles.statusBadge, { backgroundColor: item.durum === 'tamamlandi' ? '#10b981' : item.durum === 'planlandi' ? '#f59e0b' : '#ef4444' }]}>
                    <Text style={styles.statusText}>{item.durum.toUpperCase()}</Text>
                </View>
              </View>
              
              <View style={styles.cardFooter}>
                <View style={styles.infoRow}>
                  <FontAwesome6 name="calendar-day" size={14} color={theme.tabIconDefault} />
                  <Text style={styles.infoText}>{formatDate(item.ziyaret_tarihi)}</Text>
                </View>
                {item.notlar && (
                  <Text style={styles.notText} numberOfLines={2}>{item.notlar}</Text>
                )}
              </View>
            </Pressable>
          )}
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <FontAwesome6 name="route" size={50} color={theme.tabIconDefault} />
              <Text style={styles.emptyText}>Henüz şube ziyareti kaydı bulunmuyor.</Text>
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
  card: { padding: 16, borderRadius: 24, marginBottom: 15, borderWidth: 1 },
  cardHeader: { flexDirection: 'row', alignItems: 'center', marginBottom: 12, backgroundColor: 'transparent' },
  typeIcon: { width: 40, height: 40, borderRadius: 12, alignItems: 'center', justifyContent: 'center', marginRight: 12 },
  headerContent: { flex: 1, backgroundColor: 'transparent' },
  subeName: { fontSize: 16, fontWeight: '700' },
  grupName: { fontSize: 12, opacity: 0.5, marginTop: 2 },
  statusBadge: { paddingHorizontal: 8, paddingVertical: 4, borderRadius: 8 },
  statusText: { color: 'white', fontSize: 9, fontWeight: '800' },
  cardFooter: { borderTopWidth: 1, borderTopColor: 'rgba(0,0,0,0.05)', paddingTop: 12, marginTop: 4, backgroundColor: 'transparent' },
  infoRow: { flexDirection: 'row', alignItems: 'center', backgroundColor: 'transparent' },
  infoText: { fontSize: 13, opacity: 0.6, marginLeft: 8 },
  notText: { fontSize: 13, opacity: 0.7, marginTop: 8, fontStyle: 'italic' },
  emptyContainer: { alignItems: 'center', justifyContent: 'center', marginTop: 100, backgroundColor: 'transparent' },
  emptyText: { marginTop: 20, fontSize: 16, opacity: 0.5, textAlign: 'center' }
});
