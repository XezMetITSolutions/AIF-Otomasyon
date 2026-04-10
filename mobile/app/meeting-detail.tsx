import React, { useEffect, useState } from 'react';
import { StyleSheet, ScrollView, ActivityIndicator, Pressable, Alert, Animated } from 'react-native';
import { Stack, useLocalSearchParams, router } from 'expo-router';
import { FontAwesome6 } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import { fetchMeetingDetail } from '@/services/api';

export default function MeetingDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [selectedSection, setSelectedSection] = useState<'gundem' | 'katilimcilar' | 'kararlar'>('gundem');

  const loadData = async () => {
    if (!id) return;
    setLoading(true);
    setError(null);
    const result = await fetchMeetingDetail(id);
    if (result.success) {
      setData(result);
    } else {
      setError(result.message || 'Veri yüklenemedi.');
    }
    setLoading(false);
  };

  useEffect(() => { loadData(); }, [id]);

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color={theme.tint} />
      </View>
    );
  }

  if (error || !data) {
    return (
      <View style={[styles.center, { backgroundColor: theme.background, padding: 20 }]}>
        <FontAwesome6 name="circle-exclamation" size={50} color={theme.tint} />
        <Text style={{ marginTop: 20, fontSize: 18, fontWeight: 'bold' }}>Hata Oluştu</Text>
        <Text style={{ marginTop: 10, textAlign: 'center', opacity: 0.6 }}>{error || 'Toplantı verisi bulunamadı.'}</Text>
        <Pressable 
          style={[styles.actionButton, { backgroundColor: theme.tint, marginTop: 30, paddingHorizontal: 40 }]} 
          onPress={() => router.back()}
        >
          <Text style={styles.actionButtonText}>Geri Dön</Text>
        </Pressable>
      </View>
    );
  }

  const { meeting, gundem, katilimcilar, kararlar } = data;
  const mtDate = new Date(meeting.toplanti_tarihi);

  const renderSectionHeader = (title: string, icon: string, section: any) => (
    <Pressable 
      style={StyleSheet.flatten([
        styles.sectionTab, 
        selectedSection === section && styles.activeSectionTab,
        selectedSection === section && { borderBottomColor: theme.tint }
      ])}
      onPress={() => setSelectedSection(section)}
    >
      <FontAwesome6 
        name={icon} 
        size={14} 
        color={selectedSection === section ? theme.tint : theme.tabIconDefault} 
      />
      <Text style={StyleSheet.flatten([
        styles.sectionTabText, 
        { color: selectedSection === section ? theme.text : theme.tabIconDefault }
      ])}>
        {title}
      </Text>
    </Pressable>
  );

  return (
    <View style={[styles.container, { backgroundColor: theme.background }]}>
      <Stack.Screen options={{ title: 'Toplantı Detayı', headerTransparent: true, headerTintColor: '#fff' }} />
      
      <ScrollView bounces={false} showsVerticalScrollIndicator={false}>
        {/* Header Section */}
        <LinearGradient
          colors={['#009872', '#006d51']}
          style={styles.header}
        >
          <View style={styles.headerOverlay}>
            <View style={styles.dateBadge}>
              <Text style={styles.dateDay}>{mtDate.getDate()}</Text>
              <Text style={styles.dateMonth}>{mtDate.toLocaleDateString('tr-TR', { month: 'short' }).toUpperCase()}</Text>
            </View>
            <View style={styles.headerInfo}>
              <Text style={styles.title}>{meeting.baslik}</Text>
              <View style={styles.headerRow}>
                <FontAwesome6 name="location-dot" size={12} color="rgba(255,255,255,0.8)" />
                <Text style={styles.headerSubtext}>{meeting.konum || 'Konum Belirtilmedi'}</Text>
              </View>
              <View style={styles.headerRow}>
                <FontAwesome6 name="clock" size={12} color="rgba(255,255,255,0.8)" />
                <Text style={styles.headerSubtext}>
                  {mtDate.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' })}
                </Text>
              </View>
            </View>
          </View>
        </LinearGradient>

        <View style={styles.mainCard}>
          {/* Tabs */}
          <View style={[styles.sectionTabs, { borderBottomColor: theme.border }]}>
            {renderSectionHeader('Gündem', 'list-ul', 'gundem')}
            {renderSectionHeader('Katılımcılar', 'users', 'katilimcilar')}
            {renderSectionHeader('Kararlar', 'check-double', 'kararlar')}
          </View>

          {/* Content */}
          <View style={styles.contentArea}>
            {selectedSection === 'gundem' && (
              <View style={styles.sectionArea}>
                {gundem.length > 0 ? gundem.map((item: any, index: number) => (
                  <View key={index} style={[styles.itemCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
                    <View style={[styles.itemNumber, { backgroundColor: theme.tint + '15' }]}>
                      <Text style={{ color: theme.tint, fontWeight: 'bold' }}>{item.sira_no || index + 1}</Text>
                    </View>
                    <View style={styles.itemInfo}>
                      <Text style={[styles.itemTitle, { color: theme.text }]}>{item.baslik}</Text>
                      {item.aciklama && <Text style={styles.itemDesc}>{item.aciklama}</Text>}
                    </View>
                  </View>
                )) : <Text style={styles.emptyText}>Gündem maddesi bulunmuyor.</Text>}
              </View>
            )}

            {selectedSection === 'katilimcilar' && (
              <View style={styles.sectionArea}>
                {katilimcilar && katilimcilar.length > 0 ? katilimcilar.map((item: any, index: number) => (
                  <View key={index} style={StyleSheet.flatten([styles.userRow, { borderBottomColor: theme.border }])}>
                    <View style={StyleSheet.flatten([styles.userAvatar, { backgroundColor: theme.tint + '10' }])}>
                      <Text style={{ color: theme.tint, fontWeight: 'bold' }}>{(item.ad || ' ')[0]}</Text>
                    </View>
                    <View style={styles.userInfo}>
                      <Text style={StyleSheet.flatten([styles.userName, { color: theme.text }])}>{item.ad} {item.soyad}</Text>
                      <Text style={styles.userSub}>{item.email}</Text>
                    </View>
                    <View style={StyleSheet.flatten([styles.statusIndicator, { backgroundColor: item.katilim_durumu === 'katildi' ? '#10b981' : '#f59e0b' }])} />
                  </View>
                )) : <Text style={styles.emptyText}>Katılımcı bilgisi bulunmuyor.</Text>}
              </View>
            )}

            {selectedSection === 'kararlar' && (
              <View style={styles.sectionArea}>
                {kararlar.length > 0 ? kararlar.map((item: any, index: number) => (
                  <View key={index} style={[styles.decisionCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
                    <View style={styles.decisionHeader}>
                      <FontAwesome6 name="gavel" size={14} color={theme.tint} />
                      <Text style={[styles.decisionTitle, { color: theme.tint }]}>KARAR #{index + 1}</Text>
                    </View>
                    <Text style={[styles.decisionText, { color: theme.text }]}>{item.karar_metni}</Text>
                  </View>
                )) : <Text style={styles.emptyText}>Henüz karar alınmamış.</Text>}
              </View>
            )}
          </View>
        </View>
      </ScrollView>

      {/* Action Button */}
      {meeting.durum !== 'tamamlandi' && (
        <View style={styles.fabContainer}>
          <Pressable style={[styles.fab, { backgroundColor: theme.tint }]}>
            <FontAwesome6 name="file-contract" size={20} color="#fff" />
            <Text style={styles.fabText}>Tutanak Oluştur</Text>
          </Pressable>
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  header: {
    height: 240,
    paddingTop: 60,
  },
  headerOverlay: {
    flex: 1,
    padding: 24,
    backgroundColor: 'transparent',
    flexDirection: 'row',
    alignItems: 'flex-end',
  },
  dateBadge: {
    width: 70,
    height: 70,
    backgroundColor: 'rgba(255,255,255,0.2)',
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 20,
  },
  dateDay: { color: '#fff', fontSize: 24, fontWeight: '800' },
  dateMonth: { color: '#fff', fontSize: 12, fontWeight: '600' },
  headerInfo: { flex: 1, backgroundColor: 'transparent' },
  title: { color: '#fff', fontSize: 22, fontWeight: '800', marginBottom: 10 },
  headerRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 4, backgroundColor: 'transparent' },
  headerSubtext: { color: 'rgba(255,255,255,0.8)', fontSize: 13, marginLeft: 8 },
  mainCard: {
    marginTop: -30,
    borderTopLeftRadius: 32,
    borderTopRightRadius: 32,
    backgroundColor: 'transparent',
    minHeight: 500,
  },
  sectionTabs: {
    flexDirection: 'row',
    paddingHorizontal: 20,
    borderBottomWidth: 1,
    backgroundColor: 'transparent',
  },
  sectionTab: {
    flex: 1,
    paddingVertical: 18,
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'center',
    borderBottomWidth: 2,
    borderBottomColor: 'transparent',
  },
  activeSectionTab: {
    borderBottomWidth: 2,
  },
  sectionTabText: { marginLeft: 8, fontSize: 13, fontWeight: '700' },
  contentArea: { padding: 20, backgroundColor: 'transparent' },
  sectionArea: { backgroundColor: 'transparent' },
  itemCard: {
    flexDirection: 'row',
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
    marginBottom: 12,
  },
  itemNumber: {
    width: 32,
    height: 32,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 15,
  },
  itemInfo: { flex: 1, backgroundColor: 'transparent' },
  itemTitle: { fontSize: 15, fontWeight: '600' },
  itemDesc: { fontSize: 13, opacity: 0.5, marginTop: 4 },
  userRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
    borderBottomWidth: 1,
    backgroundColor: 'transparent'
  },
  userAvatar: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  userInfo: { flex: 1, backgroundColor: 'transparent' },
  userName: { fontSize: 15, fontWeight: '600' },
  userSub: { fontSize: 12, opacity: 0.5 },
  statusIndicator: { width: 8, height: 8, borderRadius: 4 },
  decisionCard: {
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
    marginBottom: 12,
  },
  decisionHeader: { flexDirection: 'row', alignItems: 'center', marginBottom: 10, backgroundColor: 'transparent' },
  decisionTitle: { fontSize: 12, fontWeight: '800', marginLeft: 8 },
  decisionText: { fontSize: 14, lineHeight: 20, fontWeight: '500' },
  emptyText: { textAlign: 'center', marginTop: 40, opacity: 0.4 },
  fabContainer: {
    position: 'absolute',
    bottom: 30,
    left: 20,
    right: 20,
    backgroundColor: 'transparent',
  },
  fab: {
    height: 56,
    borderRadius: 28,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    elevation: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
  },
  fabText: { color: '#fff', fontSize: 16, fontWeight: '700', marginLeft: 12 }
});
