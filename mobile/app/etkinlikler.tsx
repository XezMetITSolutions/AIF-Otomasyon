import React, { useEffect, useState, useMemo } from 'react';
import { StyleSheet, ActivityIndicator, RefreshControl, SectionList } from 'react-native';
import { Stack } from 'expo-router';
import { FontAwesome6 } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import { fetchEtkinlikler, fetchMeetings } from '@/services/api';

const PROJECT_COLORS = {
  primary: '#009872',
  secondary: '#004d3a',
  bgSoft: '#f8fafc',
};

export default function EtkinliklerScreen() {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  const [data, setData] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [user, setUser] = useState<any>(null);

  const loadData = async () => {
    setLoading(true);
    const userData = await AsyncStorage.getItem('user');
    const userObj = userData ? JSON.parse(userData) : null;
    setUser(userObj);

    // Etkinlikleri, Toplantıları ve Şube Ziyaretlerini paralel çekiyoruz
    const [etkResult, meetResult, ziyaResult] = await Promise.all([
      fetchEtkinlikler(),
      fetchMeetings(userObj?.id),
      fetchZiyaretler(userObj?.id)
    ]);

    let combined: any[] = [];
    if (etkResult.success) {
      combined = [...etkResult.etkinlikler.map((e: any) => ({ ...e, type: 'etkinlik' }))];
    }
    if (meetResult.success) {
      const meetings = meetResult.meetings.map((m: any) => ({ 
        etkinlik_id: 'm' + m.toplanti_id,
        baslik: m.konu,
        baslangic_tarihi: m.toplanti_tarihi,
        konum: m.mekan,
        byk_adi: 'TOPLANTI',
        byk_renk: '#3b82f6',
        type: 'toplanti'
      }));
      combined = [...combined, ...meetings];
    }
    if (ziyaResult.success) {
      const ziyaretler = ziyaResult.ziyaretler.map((z: any) => ({
        etkinlik_id: 'z' + z.ziyaret_id,
        baslik: `Şube Ziyareti: ${z.sube_adi || 'Belirtilmemiş'}`,
        baslangic_tarihi: z.ziyaret_tarihi,
        konum: z.ziyaret_yeri || '',
        byk_adi: z.byk_adi || 'ŞUBE',
        grup_adi: z.grup_adi,
        byk_renk: z.renk_kodu || '#ef4444',
        type: 'ziyaret'
      }));
      combined = [...combined, ...ziyaretler];
    }

    setData(combined);
    setLoading(false);
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await loadData();
    setRefreshing(false);
  };

  useEffect(() => { loadData(); }, []);

  const sections = useMemo(() => {
    if (!data || data.length === 0) return [];

    const now = new Date();
    now.setHours(0, 0, 0, 0);

    const filtered = data
      .filter(item => {
        const d = new Date(item.baslangic_tarihi);
        return d >= now && !isNaN(d.getTime());
      })
      .sort((a, b) => new Date(a.baslangic_tarihi).getTime() - new Date(b.baslangic_tarihi).getTime());

    const sectionsArray: { title: string; data: any[] }[] = [];
    filtered.forEach(item => {
      const date = new Date(item.baslangic_tarihi);
      const monthYear = date.toLocaleDateString('tr-TR', { month: 'long', year: 'numeric' }).toUpperCase();
      
      const lastSection = sectionsArray[sectionsArray.length - 1];
      if (lastSection && lastSection.title === monthYear) {
        lastSection.data.push(item);
      } else {
        sectionsArray.push({ title: monthYear, data: [item] });
      }
    });

    return sectionsArray;
  }, [data]);

  return (
    <View style={[styles.container, { backgroundColor: colorScheme === 'light' ? PROJECT_COLORS.bgSoft : theme.background }]}>
      <Stack.Screen options={{ title: 'Ajanda' }} />
      
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
            const isSubeZiyareti = item.baslik.toLowerCase().includes('şube') || item.baslik.toLowerCase().includes('ziyaret');
            
            return (
              <View style={[styles.card, { backgroundColor: theme.card, borderColor: theme.border }]}>
                <View style={[styles.dateCol, { borderRightColor: theme.border }]}>
                  <Text style={[styles.dateDay, { color: theme.text }]}>{date.getDate()}</Text>
                  <Text style={styles.dateWeekday}>{date.toLocaleDateString('tr-TR', { weekday: 'short' }).toUpperCase()}</Text>
                </View>
                
                <View style={styles.contentCol}>
                  <View style={styles.titleRow}>
                    <Text style={[styles.title, { color: theme.text }]} numberOfLines={2}>{item.baslik}</Text>
                  </View>
                  
                  <View style={styles.detailsRow}>
                    {item.konum && (
                      <View style={styles.detailItem}>
                        <FontAwesome6 name="location-dot" size={10} color="#64748b" />
                        <Text style={styles.detailText} numberOfLines={1}>{item.konum}</Text>
                      </View>
                    )}
                  </View>

                  <View style={styles.badgeRow}>
                    <View style={[styles.bykBadge, { backgroundColor: (isSubeZiyareti ? '#ef4444' : (item.byk_renk || PROJECT_COLORS.primary)) + '20' }]}>
                        <Text style={[styles.bykText, { color: isSubeZiyareti ? '#ef4444' : (item.byk_renk || PROJECT_COLORS.primary) }]}>
                            {item.byk_adi}
                        </Text>
                    </View>
                    {isSubeZiyareti && (
                        <View style={[styles.bykBadge, { backgroundColor: '#3b82f620', marginLeft: 8 }]}>
                            <Text style={[styles.bykText, { color: '#3b82f6' }]}>KRİTİK GÖREV</Text>
                        </View>
                    )}
                  </View>
                </View>
              </View>
            );
          }}
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <FontAwesome6 name="calendar-days" size={60} color={theme.tabIconDefault} style={{opacity: 0.3}} />
              <Text style={styles.emptyText}>Henüz bir etkinlik veya toplantı planlanmamış.</Text>
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
  dateCol: { width: 50, alignItems: 'center', justifyContent: 'center', borderRightWidth: 1, marginRight: 15 },
  dateDay: { fontSize: 22, fontWeight: '900', marginBottom: 2 },
  dateWeekday: { fontSize: 10, color: '#94a3b8', fontWeight: '700' },
  contentCol: { flex: 1 },
  titleRow: { marginBottom: 4 },
  title: { fontSize: 15, fontWeight: '700', lineHeight: 20 },
  detailsRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 10 },
  detailItem: { flexDirection: 'row', alignItems: 'center' },
  detailText: { fontSize: 11, color: '#64748b', marginLeft: 4, fontWeight: '500' },
  badgeRow: { flexDirection: 'row' },
  bykBadge: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 10 },
  bykText: { fontSize: 9, fontWeight: '800', textTransform: 'uppercase' },
  emptyContainer: { alignItems: 'center', justifyContent: 'center', marginTop: 120 },
  emptyText: { marginTop: 20, fontSize: 15, color: '#94a3b8', fontWeight: '600' }
});

