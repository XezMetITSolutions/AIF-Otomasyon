import React from 'react';
import { StyleSheet, ScrollView, Pressable, Platform, SectionList } from 'react-native';
import { SymbolView } from 'expo-symbols';
import { router } from 'expo-router';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';

const MENU_SECTIONS = [
  {
    title: 'YÖNETİM',
    data: [
      { id: 'kullanicilar', title: 'Kullanıcı Yönetimi', icon: 'person.3.fill', color: '#6366f1' },
      { id: 'byk', title: 'BYK Yönetimi', icon: 'building.2.fill', color: '#8b5cf6' },
      { id: 'subeler', title: 'Şube Yönetimi', icon: 'map.fill', color: '#06b6d4' },
      { id: 'istisareler', title: 'İstişare Sistemi', icon: 'checkmark.circle.fill', color: '#10b981' },
    ],
  },
  {
    title: 'İŞLEMLER',
    data: [
      { id: 'toplantilar', title: 'Toplantı Yönetimi', icon: 'calendar.badge.clock', color: '#f59e0b' },
      { id: 'etkinlikler', title: 'Çalışma Takvimi', icon: 'calendar', color: '#ec4899' },
      { id: 'projeler', title: 'Proje Takibi', icon: 'diagram.project.fill', color: '#3b82f6' },
      { id: 'duyurular', title: 'Duyuru Yönetimi', icon: 'bullhorn.fill', color: '#f43f5e' },
    ],
  },
  {
    title: 'ONAYLAR & TALEPLER',
    data: [
      { id: 'izinler', title: 'İzin Talepleri', icon: 'person.badge.shield.checkmark.fill', color: '#ef4444' },
      { id: 'harcamalar', title: 'Harcama Talepleri', icon: 'creditcard.fill', color: '#f97316' },
      { id: 'demirbaslar', title: 'Demirbaş Yönetimi', icon: 'box.fill', color: '#64748b' },
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
                }}
              >
                <View style={[styles.iconBox, { backgroundColor: item.color + '15' }]}>
                  <SymbolView 
                    name={{ ios: item.icon, android: 'dashboard', web: 'dashboard' } as any} 
                    tintColor={item.color} 
                    size={20} 
                  />
                </View>
                <Text style={styles.itemTitle}>{item.title}</Text>
                <SymbolView 
                  name={{ ios: 'chevron.right', android: 'chevron_right', web: 'chevron_right' } as any} 
                  tintColor={theme.tabIconDefault} 
                  size={16} 
                />
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
  container: {
    flex: 1,
  },
  scrollView: {
    flex: 1,
  },
  header: {
    paddingTop: 60,
    paddingHorizontal: 20,
    paddingBottom: 20,
    backgroundColor: 'transparent',
  },
  headerTitle: {
    fontSize: 28,
    fontWeight: '800',
  },
  section: {
    marginBottom: 25,
    paddingHorizontal: 20,
    backgroundColor: 'transparent',
  },
  sectionLabel: {
    fontSize: 13,
    fontWeight: '600',
    color: '#64748b',
    textTransform: 'uppercase',
    letterSpacing: 1,
    marginBottom: 12,
    marginLeft: 5,
  },
  item: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    borderRadius: 18,
    marginBottom: 10,
    borderWidth: 1,
    ...Platform.select({
      ios: {
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.05,
        shadowRadius: 5,
      },
      android: {
        elevation: 2,
      },
    }),
  },
  iconBox: {
    width: 40,
    height: 40,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 16,
  },
  itemTitle: {
    flex: 1,
    fontSize: 16,
    fontWeight: '600',
  },
});
