import React, { useEffect, useState } from 'react';
import { StyleSheet, FlatList, ActivityIndicator, Pressable, RefreshControl, Alert, Linking } from 'react-native';
import { Stack, router, Link } from 'expo-router';
import { FontAwesome6 } from '@expo/vector-icons';
import * as WebBrowser from 'expo-web-browser';
import * as FileSystem from 'expo-file-system';
import * as Sharing from 'expo-sharing';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import { fetchMeetings, downloadMeetingReport } from '@/services/api';

export default function MeetingsScreen() {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  const [meetings, setMeetings] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [activeTab, setActiveTab] = useState<'gelecek' | 'gecmis'>('gelecek');

  const loadMeetings = async () => {
    setLoading(true);
    const result = await fetchMeetings();
    if (result.success) {
      setMeetings(result.meetings);
    } else {
      Alert.alert('Hata', result.message || 'Veriler alınamadı.');
    }
    setLoading(false);
  };

  const onRefresh = async () => {
    setRefreshing(true);
    const result = await fetchMeetings();
    if (result.success) setMeetings(result.meetings);
    setRefreshing(false);
  };

  const handleOpenReport = async (item: any) => {
    try {
      setLoading(true);
      
      const url = `https://aifnet.islamfederasyonu.at/api/toplanti-pdf.php?id=${item.toplanti_id}`;
      
      // In-App Browser kullanarak PDF'i aç. 
      // Bu yöntem Android/iOS'da en akıcı ve "otomatik" deneyimi sağlar.
      await WebBrowser.openBrowserAsync(url, {
        toolbarColor: theme.tint,
        enableBarCollapsing: true,
        showTitle: true,
      });

    } catch (error) {
      console.error('Report Error:', error);
      Alert.alert('Hata', 'Rapor açılırken bir hata oluştu.');
    } finally {
      setLoading(false);
    }
  };

  const handleAddToCalendar = (item: any) => {
    const title = encodeURIComponent(item.baslik || 'AİF Toplantısı');
    const location = encodeURIComponent(item.konum || 'AİF Genel Merkez');
    
    // Tarih formatlama (YYYYMMDDTHHMMSSZ)
    const date = new Date(item.tarih);
    const dateStr = date.toISOString().replace(/-|:|\.\d+/g, '');
    const endDate = new Date(date.getTime() + 60 * 60 * 1000); // +1 saat
    const endDateStr = endDate.toISOString().replace(/-|:|\.\d+/g, '');

    const url = `https://www.google.com/calendar/render?action=TEMPLATE&text=${title}&dates=${dateStr}/${endDateStr}&details=AİF+Otomasyon+Toplantı+Hatırlatıcısı&location=${location}`;
    Linking.openURL(url);
  };

  useEffect(() => { loadMeetings(); }, []);

  return (
    <View style={[styles.container, { backgroundColor: theme.background }]}>
      <Stack.Screen options={{ title: 'Toplantı Yönetimi' }} />
      
      <View style={StyleSheet.flatten([styles.tabBar, { borderBottomColor: theme.border }])}>
        <Pressable 
          style={StyleSheet.flatten([
            styles.tabItem, 
            activeTab === 'gelecek' && styles.activeTabItem, 
            activeTab === 'gelecek' && { borderBottomColor: theme.tint }
          ])}
          onPress={() => setActiveTab('gelecek')}
        >
          <Text style={StyleSheet.flatten([styles.tabText, { color: activeTab === 'gelecek' ? theme.tint : theme.tabIconDefault }])}>Gelecek</Text>
        </Pressable>
        <Pressable 
          style={StyleSheet.flatten([
            styles.tabItem, 
            activeTab === 'gecmis' && styles.activeTabItem, 
            activeTab === 'gecmis' && { borderBottomColor: theme.tint }
          ])}
          onPress={() => setActiveTab('gecmis')}
        >
          <Text style={StyleSheet.flatten([styles.tabText, { color: activeTab === 'gecmis' ? theme.tint : theme.tabIconDefault }])}>Geçmiş</Text>
        </Pressable>
      </View>
      
      {loading && !refreshing ? (
        <View style={styles.center}><ActivityIndicator size="large" color={theme.tint} /></View>
      ) : (
        <FlatList
          data={meetings
            .filter(m => {
              const mDate = new Date(m.tarih);
              const now = new Date();
              now.setHours(0, 0, 0, 0); // Karşılaştırma için günü baz alalım
              return activeTab === 'gelecek' ? mDate >= now : mDate < now;
            })
            .sort((a, b) => {
              const dateA = new Date(a.tarih).getTime();
              const dateB = new Date(b.tarih).getTime();
              return activeTab === 'gelecek' ? dateA - dateB : dateB - dateA;
            })
          }
          keyExtractor={(item) => item.toplanti_id.toString()}
          renderItem={({ item }) => {
            const date = item.tarih ? new Date(item.tarih) : new Date();
            const isValidDate = !isNaN(date.getTime());
            
            // Saat bilgisini çek (eğer item.saat boşsa tarihten çıkar)
            const displayTime = item.saat || (isValidDate ? date.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' }) : '--:--');
            
            return (
                <Pressable 
                  onPress={() => {
                    if (activeTab === 'gecmis') {
                      handleOpenReport(item);
                    } else {
                      handleAddToCalendar(item);
                    }
                  }}
                  style={StyleSheet.flatten([styles.meetingCard, { backgroundColor: theme.card, borderColor: theme.border }])}
                >
                <View style={StyleSheet.flatten([styles.dateBox, { backgroundColor: theme.tint + '15' }])}>
                  <Text style={StyleSheet.flatten([styles.dayText, { color: theme.tint }])}>
                    {isValidDate ? date.getDate() : '??'}
                  </Text>
                  <Text style={StyleSheet.flatten([styles.monthText, { color: theme.tint }])}>
                    {isValidDate ? date.toLocaleDateString('tr-TR', { month: 'short' }) : '---'}
                  </Text>
                </View>
                <View style={styles.meetingInfo}>
                  <Text style={styles.meetingTitle}>{item.baslik || 'Başlıksız Toplantı'}</Text>
                  <View style={styles.meetingDetails}>
                      <FontAwesome6 name="clock" color={theme.tabIconDefault} size={12} />
                      <Text style={styles.detailText}>{displayTime}</Text>
                      <FontAwesome6 name="user-group" color={theme.tabIconDefault} size={12} style={{marginLeft: 10}} />
                      <Text style={styles.detailText}>{item.katilimci_sayisi || 0} Kişi</Text>
                  </View>
                  <View style={StyleSheet.flatten([styles.statusBadge, { backgroundColor: item.durum === 'tamamlandi' ? '#10b981' : item.durum === 'iptal' ? '#ef4444' : '#f59e0b' }])}>
                      <Text style={styles.statusText}>{(item.durum || 'bekliyor').toUpperCase()}</Text>
                  </View>
                </View>
                  <FontAwesome6 name="chevron-right" color={theme.tabIconDefault} size={14} />
                </Pressable>
            );
          }}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={theme.tint} />}
          contentContainerStyle={styles.listContent}
          ListEmptyComponent={<Text style={styles.emptyText}>{activeTab === 'gelecek' ? 'Gelecek toplantı bulunamadı.' : 'Geçmiş toplantı bulunamadı.'}</Text>}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  tabBar: {
    flexDirection: 'row',
    borderBottomWidth: 1,
    paddingHorizontal: 8,
  },
  tabItem: {
    flex: 1,
    paddingVertical: 14,
    alignItems: 'center',
    borderBottomWidth: 2,
    borderBottomColor: 'transparent',
  },
  activeTabItem: {
    borderBottomWidth: 2,
  },
  tabText: {
    fontSize: 15,
    fontWeight: '600',
  },
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
