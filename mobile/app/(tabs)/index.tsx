import React, { useEffect, useState } from 'react';
import { StyleSheet, ScrollView, Dimensions, Pressable, ActivityIndicator, RefreshControl, Image } from 'react-native';
import { router } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { FontAwesome6 } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import { fetchStats } from '@/services/api';

const { width } = Dimensions.get('window');

const PROJECT_COLORS = {
  primary: '#009872',
  primaryDark: '#007a5e',
  secondary: '#004d3a',
  accent: '#f5f5f5',
  bgSoft: '#f8fafc',
};

export default function DashboardScreen() {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [stats, setStats] = useState<any>(null);
  const [user, setUser] = useState<any>(null);

  const loadData = async () => {
    setLoading(true);
    const userData = await AsyncStorage.getItem('user');
    const userObj = userData ? JSON.parse(userData) : null;
    setUser(userObj);

    const statsResult = await fetchStats(userObj?.id);
    if (statsResult.success) setStats(statsResult.stats);
    
    setLoading(false);
  };

  const onRefresh = async () => {
    setRefreshing(true);
    const statsResult = await fetchStats(user?.id);
    if (statsResult.success) setStats(statsResult.stats);
    setRefreshing(false);
  };

  useEffect(() => {
    loadData();
  }, []);

  if (loading && !refreshing) {
    return (
      <View style={[styles.container, styles.center, { backgroundColor: theme.background }]}>
        <ActivityIndicator size="large" color={PROJECT_COLORS.primary} />
      </View>
    );
  }

  const QUICK_ACTIONS = [
    { title: 'Harcama', icon: 'money-bill-transfer', color: '#f59e0b', route: '/tasks?type=harcama&scope=my' },
    { title: 'Toplantılar', icon: 'users-rectangle', color: '#10b981', route: '/meetings' },
    { title: 'Raggal', icon: 'calendar-day', color: '#8b5cf6', route: '/raggal' },
    { title: 'Şube Ziyaretleri', icon: 'map-location-dot', color: '#06b6d4', route: '/sube-ziyaretleri' },
  ];

  return (
    <ScrollView 
      style={[styles.container, { backgroundColor: colorScheme === 'light' ? PROJECT_COLORS.bgSoft : theme.background }]}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={PROJECT_COLORS.primary} />}
      showsVerticalScrollIndicator={false}
    >
      <View style={styles.topSection}>
        <View style={styles.logoRow}>
          <Image 
            source={require('@/assets/images/logo.png')} 
            style={styles.logo} 
            resizeMode="contain"
          />
          <Pressable 
            style={[styles.profileButton, { backgroundColor: theme.card, borderColor: theme.border }]} 
            onPress={() => router.push('/(tabs)/menu')}
          >
            <FontAwesome6 name="bars-staggered" size={20} color={theme.text} />
          </Pressable>
        </View>

        <View style={styles.welcomeBox}>
          <Text style={[styles.greetingText, { color: theme.text }]}>Selamün Aleyküm,</Text>
          <Text style={[styles.userNameText, { color: theme.text }]}>{user?.name?.split(' ')[0] || 'Değerli Üyemiz'}</Text>
        </View>

        <LinearGradient
          colors={[PROJECT_COLORS.primary, PROJECT_COLORS.secondary]}
          style={styles.heroCard}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
        >
          <View style={styles.heroContent}>
            <Text style={styles.heroSubtitle}>AİF Kurumsal</Text>
            <Text style={styles.heroTitle}>Dijital Otomasyon</Text>
            <View style={styles.heroStatusBox}>
              <View style={styles.dot} />
              <Text style={styles.heroStatusText}>Sistem Aktif</Text>
            </View>
          </View>
          <View style={styles.heroIconBox}>
             <FontAwesome6 name="earth-europe" size={100} color="rgba(255,255,255,0.1)" style={styles.heroBgIcon} />
          </View>
        </LinearGradient>
      </View>

      <View style={styles.content}>
        <View style={styles.sectionHeader}>
          <Text style={[styles.sectionTitle, { color: theme.text }]}>Hızlı Menü</Text>
        </View>
        
        <View style={styles.actionGrid}>
          {QUICK_ACTIONS.map((action, idx) => (
            <Pressable 
              key={idx} 
              style={[styles.actionCard, { backgroundColor: theme.card, shadowColor: action.color }]}
              onPress={() => router.push(action.route as any)}
            >
              <View style={[styles.actionIcon, { backgroundColor: action.color + '15' }]}>
                <FontAwesome6 name={action.icon as any} size={24} color={action.color} />
              </View>
              <Text style={[styles.actionText, { color: theme.text }]}>{action.title}</Text>
            </Pressable>
          ))}
        </View>

        <View style={styles.statusSection}>
          <Text style={[styles.sectionTitle, { color: theme.text }]}>Güncel Durum</Text>
          <View style={[styles.infoContainer, { backgroundColor: theme.card }]}>
            <StatusItem 
                icon="clock" 
                color="#f59e0b" 
                label="Bekleyen Talepler" 
                value={stats?.bekleyen_izin + stats?.bekleyen_harcama || 0} 
            />
            <View style={[styles.vDivider, { backgroundColor: theme.border }]} />
            <StatusItem 
                icon="calendar-check" 
                color={PROJECT_COLORS.primary} 
                label="Toplantılar" 
                value={stats?.toplam_toplanti || 0} 
            />
            <View style={[styles.vDivider, { backgroundColor: theme.border }]} />
            <StatusItem 
                icon="rocket" 
                color="#3b82f6" 
                label="Projeler" 
                value={stats?.toplam_proje || 0} 
            />
          </View>
        </View>

        <Pressable 
          style={[styles.announcementCard, { backgroundColor: PROJECT_COLORS.primary }]}
          onPress={() => router.push('/(tabs)/menu')}
        >
          <View style={styles.announcementContent}>
            <Text style={styles.announcementTitle}>Kurumsal Duyurular</Text>
            <Text style={styles.announcementDesc}>En son güncellemeleri ve bildirimleri görüntüle</Text>
          </View>
          <View style={styles.announcementBtn}>
            <FontAwesome6 name="arrow-right" size={14} color={PROJECT_COLORS.primary} />
          </View>
        </Pressable>
      </View>
      <View style={{ height: 120 }} />
    </ScrollView>
  );
}

function StatusItem({ icon, color, label, value }: any) {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  return (
    <View style={styles.statusItem}>
      <Text style={[styles.statusValue, { color: theme.text }]}>{value}</Text>
      <Text style={styles.statusLabel}>{label}</Text>
      <View style={[styles.miniIcon, { backgroundColor: color + '15' }]}>
        <FontAwesome6 name={icon} size={10} color={color} />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  center: { alignItems: 'center', justifyContent: 'center' },
  topSection: { paddingHorizontal: 20, paddingTop: 50, paddingBottom: 15 },
  logoRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 },
  logo: { width: 80, height: 40 },
  profileButton: { width: 42, height: 42, borderRadius: 12, alignItems: 'center', justifyContent: 'center', borderWidth: 1 },
  welcomeBox: { marginBottom: 25 },
  greetingText: { fontSize: 18, opacity: 0.5, fontWeight: '500' },
  userNameText: { fontSize: 32, fontWeight: '800', letterSpacing: -1, marginTop: 2 },
  heroCard: { borderRadius: 28, padding: 24, height: 160, justifyContent: 'center', overflow: 'hidden', elevation: 12, shadowOffset: { width: 0, height: 8 }, shadowOpacity: 0.25, shadowRadius: 12 },
  heroContent: { backgroundColor: 'transparent', zIndex: 1 },
  heroTitle: { color: '#fff', fontSize: 26, fontWeight: '900', letterSpacing: -0.5 },
  heroSubtitle: { color: 'rgba(255,255,255,0.7)', fontSize: 14, fontWeight: '600', marginBottom: 4 },
  heroStatusBox: { flexDirection: 'row', alignItems: 'center', marginTop: 12, backgroundColor: 'rgba(255,255,255,0.15)', alignSelf: 'flex-start', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 20 },
  dot: { width: 6, height: 6, borderRadius: 3, backgroundColor: '#4ade80', marginRight: 6 },
  heroStatusText: { color: '#fff', fontSize: 11, fontWeight: '700' },
  heroIconBox: { position: 'absolute', right: -20, bottom: -20 },
  heroBgIcon: { opacity: 0.3 },
  content: { paddingHorizontal: 20 },
  sectionHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 15, marginTop: 25 },
  sectionTitle: { fontSize: 20, fontWeight: '800', letterSpacing: -0.5 },
  actionGrid: { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between' },
  actionCard: { width: (width - 55) / 2, padding: 20, borderRadius: 24, marginBottom: 15, alignItems: 'center', elevation: 4, shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.1, shadowRadius: 6 },
  actionIcon: { width: 56, height: 56, borderRadius: 18, alignItems: 'center', justifyContent: 'center', marginBottom: 14 },
  actionText: { fontSize: 14, fontWeight: '700' },
  statusSection: { marginTop: 10 },
  infoContainer: { flexDirection: 'row', borderRadius: 28, padding: 20, justifyContent: 'space-between', elevation: 2 },
  statusItem: { alignItems: 'center', flex: 1 },
  statusLabel: { fontSize: 10, fontWeight: '600', color: '#94a3b8', marginTop: 4, textAlign: 'center' },
  statusValue: { fontSize: 20, fontWeight: '800' },
  miniIcon: { padding: 4, borderRadius: 6, marginTop: 6 },
  vDivider: { width: 1, height: '70%', alignSelf: 'center', opacity: 0.5 },
  announcementCard: { flexDirection: 'row', alignItems: 'center', padding: 20, borderRadius: 28, marginTop: 25, elevation: 6 },
  announcementContent: { flex: 1 },
  announcementTitle: { fontSize: 17, fontWeight: '800', color: '#fff' },
  announcementDesc: { fontSize: 12, color: 'rgba(255,255,255,0.8)', marginTop: 4 },
  announcementBtn: { width: 36, height: 36, borderRadius: 12, backgroundColor: '#fff', alignItems: 'center', justifyContent: 'center' },
});
