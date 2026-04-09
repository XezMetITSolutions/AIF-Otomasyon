import React from 'react';
import { StyleSheet, ScrollView, Pressable, Platform } from 'react-native';
import { FontAwesome6 } from '@expo/vector-icons';
import { router } from 'expo-router';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useFocusEffect } from 'expo-router';

const MENU_SECTIONS = [
  {
    title: 'ORTAK ALAN',
    data: [
      { id: 'dashboard', title: 'Kontrol Paneli', icon: 'gauge-high', color: '#6366f1', route: '/(tabs)' },
      { id: 'duyurular', title: 'Duyurular', icon: 'bullhorn', color: '#f43f5e', route: '/(tabs)/menu', moduleKey: 'baskan_duyurular' },
      { id: 'takvim', title: 'Çalışma Takvimi', icon: 'calendar-days', color: '#ec4899', route: '/etkinlikler', moduleKey: 'baskan_etkinlikler' },
      { id: 'toplantilar', title: 'Toplantılar', icon: 'users-gear', color: '#f59e0b', route: '/meetings', moduleKey: 'baskan_toplantilar' },
    ],
  },
  {
    title: 'SİSTEM YÖNETİMİ',
    isAdminOnly: true,
    data: [
      { id: 'uyeler', title: 'Üyeler', icon: 'users', color: '#3b82f6', route: '/users' },
      { id: 'admin_users', title: 'Kullanıcı Yönetimi', icon: 'users-gear', color: '#6366f1', route: '/users' },
      { id: 'admin_byk', title: 'BYK Yönetimi', icon: 'building-shield', color: '#8b5cf6', route: '/byk' },
      { id: 'admin_alt_birimler', title: 'Alt Birimler', icon: 'sitemap', color: '#10b981', route: '/alt-birimler' },
      { id: 'admin_subeler', title: 'Şubeler', icon: 'map-location-dot', color: '#06b6d4', route: '/subeler' },
    ],
  },
  {
    title: 'İSTİŞARE & RAPOR',
    data: [
      { id: 'istisareler', title: 'İstişareler', icon: 'check-to-slot', color: '#10b981', route: '/istisareler', moduleKey: 'at_istisare' },
    ],
  },
  {
    title: 'YÖNETİM MODÜLLERİ',
    data: [
      { id: 'onay_izin', title: 'İzin Onayları', icon: 'calendar-check', color: '#ef4444', route: '/tasks?type=izin', moduleKey: 'baskan_izin_talepleri' },
      { id: 'onay_harcama', title: 'Rezervasyon Onayları', icon: 'file-circle-check', color: '#f97316', route: '/tasks?type=harcama', moduleKey: 'baskan_harcama_talepleri' },
      { id: 'onay_iade', title: 'İade Onayları', icon: 'hand-holding-dollar', color: '#0ea5e9', route: '/tasks?type=iade', moduleKey: 'baskan_iade_formlari' },
      { id: 'onay_demirbas', title: 'Demirbaş Talepleri', icon: 'box-open', color: '#64748b', route: '/demirbaslar', moduleKey: 'baskan_demirbas_yonetimi' },
      { id: 'onay_raggal', title: 'Raggal Talepleri', icon: 'calendar-day', color: '#8b5cf6', route: '/raggal', moduleKey: 'baskan_raggal_talepleri' },
      { id: 'projeler', title: 'Proje Yönetimi', icon: 'diagram-project', color: '#3b82f6', route: '/projeler', moduleKey: 'baskan_projeler' },
      { id: 'sube_ziyaretleri', title: 'Şube Ziyaretleri', icon: 'map-location-dot', color: '#06b6d4', route: '/sube-ziyaretleri', moduleKey: 'baskan_sube_ziyaretleri' },
    ],
  },
  {
    title: 'KİŞİSEL MODÜLLER',
    data: [
      { id: 'talep_izin', title: 'İzin Taleplerim', icon: 'person-walking', color: '#ef4444', route: '/tasks?type=izin&scope=my', moduleKey: 'uye_izin_talepleri' },
      { id: 'talep_harcama', title: 'Rezervasyon Taleplerim', icon: 'file-invoice-dollar', color: '#f97316', route: '/tasks?type=harcama&scope=my', moduleKey: 'uye_harcama_talepleri' },
      { id: 'talep_iade', title: 'İade Talebi Oluştur', icon: 'file-invoice-dollar', color: '#0ea5e9', route: '/tasks?type=iade&scope=my', moduleKey: 'uye_iade_formu' },
      { id: 'talep_demirbas', title: 'Demirbaş Talep', icon: 'box', color: '#64748b', route: '/demirbaslar?scope=my', moduleKey: 'uye_demirbas_talep' },
      { id: 'talep_raggal', title: 'Raggal Rezervasyon', icon: 'calendar-plus', color: '#8b5cf6', route: '/raggal?scope=my', moduleKey: 'uye_raggal_talep' },
      { id: 'projelerim', title: 'Projelerim', icon: 'list-check', color: '#3b82f6', route: '/projeler?scope=my', moduleKey: 'uye_projeler' },
    ],
  },
];

export default function MenuScreen() {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  const [user, setUser] = React.useState<any>(null);

  useFocusEffect(
    React.useCallback(() => {
      const loadUser = async () => {
        const userData = await AsyncStorage.getItem('user');
        if (userData) {
          setUser(JSON.parse(userData));
        }
      };
      loadUser();
    }, [])
  );

  const visibleSections = MENU_SECTIONS.map(section => {
    // Admin Only section check
    if (section.isAdminOnly && user?.role !== 'super_admin') {
      return null;
    }

    // Filter items within section based on module permissions
    const filteredItems = section.data.filter(item => {
      // If item has a moduleKey, check permissions
      if (item.moduleKey && user?.permissions) {
        return !!user.permissions[item.moduleKey];
      }
      return true; // No key means visible to all (who can see the section)
    });

    if (filteredItems.length === 0) return null;

    return { ...section, data: filteredItems };
  }).filter(Boolean) as any[];

  return (
    <View style={[styles.container, { backgroundColor: theme.background }]}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Menü</Text>
      </View>

      <ScrollView style={styles.scrollView} showsVerticalScrollIndicator={false}>
        {visibleSections.map((section, idx) => (
          <View key={idx} style={styles.section}>
            <Text style={styles.sectionLabel}>{section.title}</Text>
            {section.data.map((item) => (
              <Pressable 
                key={item.id} 
                style={({ pressed }) => [
                  styles.item, 
                  { 
                    backgroundColor: theme.card, 
                    borderColor: theme.border,
                    opacity: pressed ? 0.7 : 1,
                    transform: [{ scale: pressed ? 0.98 : 1 }]
                  }
                ]}
                onPress={() => router.push(item.route as any)}
              >
                <View style={[styles.iconBox, { backgroundColor: item.color + '15' }]}>
                  <FontAwesome6 name={item.icon as any} color={item.color} size={18} />
                </View>
                <Text style={[styles.itemTitle, { color: theme.text }]}>{item.title}</Text>
                <FontAwesome6 name="chevron-right" color={theme.tabIconDefault} size={12} />
              </Pressable>
            ))}
          </View>
        ))}
        <View style={{ height: 100 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  scrollView: { flex: 1 },
  header: { paddingTop: 60, paddingHorizontal: 20, paddingBottom: 20 },
  headerTitle: { fontSize: 32, fontWeight: '800', letterSpacing: -0.5 },
  section: { marginBottom: 30, paddingHorizontal: 20 },
  sectionLabel: { fontSize: 13, fontWeight: '700', color: '#94a3b8', textTransform: 'uppercase', letterSpacing: 1.5, marginBottom: 15, marginLeft: 4 },
  item: { 
    flexDirection: 'row', 
    alignItems: 'center', 
    padding: 16, 
    borderRadius: 20, 
    marginBottom: 12, 
    borderWidth: 1,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 10,
    elevation: 2,
  },
  iconBox: { width: 42, height: 42, borderRadius: 12, alignItems: 'center', justifyContent: 'center', marginRight: 16 },
  itemTitle: { flex: 1, fontSize: 16, fontWeight: '600' },
});
