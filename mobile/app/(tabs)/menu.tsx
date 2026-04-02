import React from 'react';
import { StyleSheet, ScrollView, Pressable, Platform } from 'react-native';
import { FontAwesome6 } from '@expo/vector-icons';
import { router } from 'expo-router';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';

const MENU_SECTIONS = [
  {
    title: 'YÖNETİM',
    data: [
      { id: 'kullanicilar', title: 'Kullanıcı Yönetimi', icon: 'users', color: '#6366f1' },
      { id: 'byk', title: 'BYK Yönetimi', icon: 'building', color: '#8b5cf6' },
      { id: 'alt-birimler', title: 'Alt Birimler', icon: 'sitemap', color: '#10b981' },
      { id: 'istisareler', title: 'İstişare Sistemi', icon: 'check-to-slot', color: '#10b981' },
      { id: 'subeler', title: 'Şube Yönetimi', icon: 'map-location-dot', color: '#06b6d4' },
    ],
  },
  {
    title: 'İŞLEMLER',
    data: [
      { id: 'toplantilar', title: 'Toplantı Yönetimi', icon: 'users-gear', color: '#f59e0b' },
      { id: 'etkinlikler', title: 'Çalışma Takvimi', icon: 'calendar-days', color: '#ec4899' },
      { id: 'projeler', title: 'Proje Takibi', icon: 'diagram-project', color: '#3b82f6' },
      { id: 'duyurular', title: 'Duyuru Yönetimi', icon: 'bullhorn', color: '#f43f5e' },
    ],
  },
  {
    title: 'ONAYLAR & TALEPLER',
    data: [
      { id: 'izinler', title: 'İzin Talepleri', icon: 'calendar-check', color: '#ef4444' },
      { id: 'harcamalar', title: 'Harcama Talepleri', icon: 'calendar-check', color: '#f97316' },
      { id: 'demirbaslar', title: 'Demirbaş Yönetimi', icon: 'box', color: '#64748b' },
    ],
  },
];

export default function MenuScreen() {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];

  return (
    <View style={[styles.container, { backgroundColor: theme.background }]}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Menü</Text>
      </View>

      <ScrollView style={styles.scrollView}>
        {MENU_SECTIONS.map((section, idx) => (
          <View key={idx} style={styles.section}>
            <Text style={styles.sectionLabel}>{section.title}</Text>
            {section.data.map((item) => (
              <Pressable 
                key={item.id} 
                style={[styles.item, { backgroundColor: theme.card, borderColor: theme.border }]}
                onPress={() => {
                  if (item.id === 'kullanicilar') router.push('/users');
                  if (item.id === 'toplantilar') router.push('/meetings');
                  if (item.id === 'izinler') router.push('/tasks?type=izin');
                  if (item.id === 'harcamalar') router.push('/tasks?type=harcama');
                  if (item.id === 'projeler') router.push('/projeler');
                  // Diğerleri için taslak yönlendirme
                  if (['byk', 'subeler', 'istisareler'].includes(item.id)) {
                      router.push(`/${item.id}`);
                  }
                }}
              >
                <View style={[styles.iconBox, { backgroundColor: item.color + '15' }]}>
                  <FontAwesome6 name={item.icon as any} color={item.color} size={18} />
                </View>
                <Text style={styles.itemTitle}>{item.title}</Text>
                <FontAwesome6 name="chevron-right" color={theme.tabIconDefault} size={14} />
              </Pressable>
            ))}
          </View>
        ))}
        <View style={{ height: 40 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  scrollView: { flex: 1 },
  header: { paddingTop: 60, paddingHorizontal: 20, paddingBottom: 20 },
  headerTitle: { fontSize: 28, fontWeight: '800' },
  section: { marginBottom: 25, paddingHorizontal: 20 },
  sectionLabel: { fontSize: 13, fontWeight: '600', color: '#64748b', textTransform: 'uppercase', letterSpacing: 1, marginBottom: 12, marginLeft: 5 },
  item: { flexDirection: 'row', alignItems: 'center', padding: 16, borderRadius: 18, marginBottom: 10, borderWidth: 1, elevation: 1 },
  iconBox: { width: 36, height: 36, borderRadius: 10, alignItems: 'center', justifyContent: 'center', marginRight: 16 },
  itemTitle: { flex: 1, fontSize: 16, fontWeight: '600' },
});
