import React, { useEffect, useState } from 'react';
import { StyleSheet, FlatList, ActivityIndicator, Pressable, RefreshControl } from 'react-native';
import { Stack } from 'expo-router';
import { FontAwesome6 } from '@expo/vector-icons';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import { fetchProjeler } from '@/services/api';

export default function ProjelerScreen() {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  const [data, setData] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const loadData = async () => {
    setLoading(true);
    const result = await fetchProjeler();
    if (result.success) setData(result.projeler);
    setLoading(false);
  };

  useEffect(() => { loadData(); }, []);

  return (
    <View style={[styles.container, { backgroundColor: theme.background }]}>
      <Stack.Screen options={{ title: 'Proje Takibi' }} />
      {loading && !refreshing ? (
        <View style={styles.center}><ActivityIndicator size="large" color={theme.tint} /></View>
      ) : (
        <FlatList
          data={data}
          keyExtractor={(item) => item.proje_id.toString()}
          renderItem={({ item }) => (
            <Pressable style={[styles.card, { backgroundColor: theme.card, borderColor: theme.border }]}>
               <View style={[styles.iconBox, { backgroundColor: '#3b82f615' }]}>
                <FontAwesome6 name="diagram-project" color="#3b82f6" size={18} />
              </View>
              <View style={styles.info}>
                <Text style={styles.title}>{item.baslik}</Text>
                <View style={styles.statusRow}>
                    <Text style={styles.subtitle}>{item.durum.toUpperCase()}</Text>
                    <Text style={styles.sep}> • </Text>
                    <Text style={styles.subtitle}>{item.not_sayisi} Not</Text>
                </View>
              </View>
              <FontAwesome6 name="chevron-right" color={theme.tabIconDefault} size={14} />
            </Pressable>
          )}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={loadData} tintColor={theme.tint} />}
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
  card: { flexDirection: 'row', alignItems: 'center', padding: 16, borderRadius: 15, marginBottom: 12, borderWidth: 1 },
  iconBox: { width: 44, height: 44, borderRadius: 12, alignItems: 'center', justifyContent: 'center', marginRight: 15 },
  info: { flex: 1, backgroundColor: 'transparent' },
  title: { fontSize: 16, fontWeight: '700' },
  statusRow: { flexDirection: 'row', alignItems: 'center', marginTop: 4, backgroundColor: 'transparent' },
  subtitle: { fontSize: 12, opacity: 0.5 },
  sep: { opacity: 0.3 }
});
