import React, { useEffect, useState, useMemo } from 'react';
import { StyleSheet, ActivityIndicator, RefreshControl, SectionList, Pressable } from 'react-native';
import { Stack } from 'expo-router';
import { FontAwesome6 } from '@expo/vector-icons';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import { fetchEtkinlikler } from '@/services/api';

const PROJECT_COLORS = {
  primary: '#009872',
  secondary: '#004d3a',
  bgSoft: '#f8fafc',
};

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
    if (result.success) setEtkinlikler(result.etkinlikler);
    setRefreshing(false);
  };

  useEffect(() => { loadData(); }, []);

  // Takvim Verilerini İşle (Sırala ve Ay Bazlı Gruplandır)
  const sections = useMemo(() => {
    if (!etkinlikler || etkinlikler.length === 0) return [];

    const now = new Date();
    now.setHours(0, 0, 0, 0); // Sadece günü baz alalım

    // 1. Sadece bugünden sonraki etkinlikleri al ve tarihe göre sırala
    const upcoming = etkinlikler
      .filter(item => new Date(item.baslangic_tarihi) >= now)
      .sort((a, b) => new Date(a.baslangic_tarihi).getTime() - new Date(b.baslangic_tarihi).getTime());

    // 2. Gruplandır
    const sectionsArray: { title: string; data: any[] }[] = [];
    upcoming.forEach(item => {
      const date = new Date(item.baslangic_tarihi);
      const monthYear = date.toLocaleDateString('tr-TR', { month: 'long', year: 'numeric' }).toUpperCase();
      
      const existingSection = sectionsArray.find(s => s.title === monthYear);
      if (existingSection) {
        existingSection.data.push(item);
      } else {
        sectionsArray.push({ title: monthYear, data: [item] });
      }
    });

    return sectionsArray;
  }, [etkinlikler]);

  const formatTime = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });
  };

  return (
    <View style={[styles.container, { backgroundColor: colorScheme === 'light' ? PROJECT_COLORS.bgSoft : theme.background }]}>
      <Stack.Screen options={{ title: 'Çalışma Takvimi' }} />
      
      {loading && !refreshing ? (
        <View style={styles.center}><ActivityIndicator size="large" color={PROJECT_COLORS.primary} /></View>
      ) : (
        <SectionList
          sections={sections}
          keyExtractor={(item) => item.etkinlik_id.toString()}
          stickySectionHeadersEnabled={true}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={PROJECT_COLORS.primary} />}
          renderSectionHeader={({ section: { title } }) => (
            <View style={[styles.sectionHeader, { backgroundColor: colorScheme === 'light' ? PROJECT_COLORS.bgSoft : theme.card }]}>
              <Text style={[styles.sectionHeaderText, { color: PROJECT_COLORS.primary }]}>{title}</Text>
            </View>
          )}
          renderItem={({ item }) => {
            const date = new Date(item.baslangic_tarihi);
            return (
              <View style={[styles.card, { backgroundColor: theme.card, borderColor: theme.border }]}>
                <View style={styles.dateCol}>
                  <Text style={[styles.dateDay, { color: theme.text }]}>{date.getDate()}</Text>
                  <Text style={styles.dateWeekday}>{date.toLocaleDateString('tr-TR', { weekday: 'short' }).toUpperCase()}</Text>
                </View>
                
                <View style={styles.contentCol}>
                  <View style={styles.titleRow}>
                    <Text style={[styles.title, { color: theme.text }]} numberOfLines={2}>{item.baslik}</Text>
                  </View>
                  
                  <View style={styles.detailsRow}>
                    <View style={styles.detailItem}>
                      <FontAwesome6 name="clock" size={10} color={PROJECT_COLORS.primary} />
                      <Text style={styles.detailText}>{formatTime(item.baslangic_tarihi)}</Text>
                    </View>
                    {item.konum && (
                      <View style={[styles.detailItem, { marginLeft: 12 }]}>
                        <FontAwesome6 name="location-dot" size={10} color="#ef4444" />
                        <Text style={styles.detailText} numberOfLines={1}>{item.konum}</Text>
                      </View>
                    )}
                  </View>

                  <View style={styles.badgeRow}>
                    <View style={[styles.bykBadge, { backgroundColor: item.byk_renk || PROJECT_COLORS.primary }]}>
                        <Text style={styles.bykText}>{item.byk_adi}</Text>
                    </View>
                  </View>
                </View>
              </View>
            );
          }}
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <FontAwesome6 name="calendar-days" size={60} color={theme.tabIconDefault} style={{opacity: 0.3}} />
              <Text style={styles.emptyText}>Henüz bir etkinlik planlanmamış.</Text>
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
  listContent: { paddingBottom: 100 },
  sectionHeader: { paddingHorizontal: 20, paddingVertical: 12 },
  sectionHeaderText: { fontSize: 13, fontWeight: '800', letterSpacing: 1.2 },
  card: { 
    flexDirection: 'row', 
    marginHorizontal: 16,
    padding: 16, 
    borderRadius: 24, 
    marginBottom: 12, 
    borderWidth: 1,
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 8,
  },
  dateCol: { width: 50, alignItems: 'center', justifyContent: 'center', borderRightWidth: 1, borderRightColor: '#eee', marginRight: 15 },
  dateDay: { fontSize: 22, fontWeight: '900', marginBottom: 2 },
  dateWeekday: { fontSize: 10, color: '#94a3b8', fontWeight: '700' },
  contentCol: { flex: 1 },
  titleRow: { marginBottom: 6 },
  title: { fontSize: 15, fontWeight: '700', lineHeight: 20 },
  detailsRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 10 },
  detailItem: { flexDirection: 'row', alignItems: 'center' },
  detailText: { fontSize: 11, color: '#64748b', marginLeft: 4, fontWeight: '500' },
  badgeRow: { flexDirection: 'row' },
  bykBadge: { paddingHorizontal: 8, paddingVertical: 3, borderRadius: 8 },
  bykText: { color: 'white', fontSize: 9, fontWeight: '800', textTransform: 'uppercase' },
  emptyContainer: { alignItems: 'center', justifyContent: 'center', marginTop: 120 },
  emptyText: { marginTop: 20, fontSize: 15, color: '#94a3b8', fontWeight: '600' }
});
