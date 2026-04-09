import React, { useEffect, useState } from 'react';
import { StyleSheet, ScrollView, Dimensions, Pressable, ActivityIndicator, RefreshControl } from 'react-native';
import { router } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { FontAwesome6 } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import { fetchStats } from '@/services/api';

const { width } = Dimensions.get('window');

// Proje Renkleri
const PROJECT_COLORS = {
  primary: '#009872',
  primaryDark: '#007a5e',
  accent: '#f5f5f5',
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
    { title: 'İzin Talebi', icon: 'person-walking', color: '#ef4444', route: '/tasks?type=izin&scope=my' },
    { title: 'Harcama Talebi', icon: 'file-invoice-dollar', color: '#f59e0b', route: '/tasks?type=harcama&scope=my' },
    { title: 'Toplantılar', icon: 'users-rectangle', color: PROJECT_COLORS.primary, route: '/meetings' },
    { title: 'Projelerim', icon: 'list-check', color: '#3b82f6', route: '/projeler?scope=my' },
  ];

  return (
    <ScrollView 
      style={[styles.container, { backgroundColor: theme.background }]}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={PROJECT_COLORS.primary} />}
      showsVerticalScrollIndicator={false}
    >
      <View style={styles.topSection}>
        <View style={styles.headerRow}>
          <View style={styles.welcomeBox}>
            <Text style={styles.greetingText}>Hoş Geldiniz,</Text>
            <Text style={styles.userNameText}>{user?.name || 'Kullanıcı'}</Text>
          </View>
          <Pressable style={[styles.profileButton, { backgroundColor: theme.card, borderColor: theme.border }]} onPress={() => router.push('/(tabs)/two')}>
            <FontAwesome6 name="user-gear" size={20} color={theme.text} />
          </Pressable>
        </View>

        <LinearGradient
          colors={[PROJECT_COLORS.primary, PROJECT_COLORS.primaryDark]}
          style={styles.heroCard}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
        >
          <View style={styles.heroContent}>
            <Text style={styles.heroTitle}>AİFNET</Text>
            <Text style={styles.heroSubtitle}>Yönetim ve Koordinasyon Sistemi</Text>
          </View>
          <View style={styles.heroIconBox}>
             <FontAwesome6 name="shield-halved" size={70} color="rgba(255,255,255,0.15)" style={styles.heroBgIcon} />
          </View>
        </LinearGradient>
      </View>

      <View style={styles.content}>
        <Text style={styles.sectionTitle}>Hızlı İşlemler</Text>
        <View style={styles.actionGrid}>
          {QUICK_ACTIONS.map((action, idx) => (
            <Pressable 
              key={idx} 
              style={[styles.actionCard, { backgroundColor: theme.card, borderColor: theme.border }]}
              onPress={() => router.push(action.route as any)}
            >
              <View style={[styles.actionIcon, { backgroundColor: action.color + '15' }]}>
                <FontAwesome6 name={action.icon as any} size={22} color={action.color} />
              </View>
              <Text style={[styles.actionText, { color: theme.text }]}>{action.title}</Text>
            </Pressable>
          ))}
        </View>

        <View style={styles.statusSection}>
          <Text style={styles.sectionTitle}>Görev Özeti</Text>
          <View style={[styles.infoCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
            <StatusRow icon="hourglass-half" color="#f59e0b" label="Onay Bekleyenler" value={stats?.bekleyen_izin + stats?.bekleyen_harcama || 0} />
            <View style={[styles.divider, { backgroundColor: theme.border }]} />
            <StatusRow icon="calendar-days" color={PROJECT_COLORS.primary} label="Aktif Toplantılar" value={stats?.toplam_toplanti || 0} />
            <View style={[styles.divider, { backgroundColor: theme.border }]} />
            <StatusRow icon="diagram-project" color="#3b82f6" label="Aktif Projelerim" value={stats?.toplam_proje || 0} />
          </View>
        </View>

        <Pressable 
          style={[styles.announcementCard, { backgroundColor: PROJECT_COLORS.primary + '10' }]}
          onPress={() => router.push('/(tabs)/menu')}
        >
          <View style={[styles.announcementIcon, { backgroundColor: PROJECT_COLORS.primary }]}>
            <FontAwesome6 name="bullhorn" size={18} color="#fff" />
          </View>
          <View style={styles.announcementContent}>
            <Text style={[styles.announcementTitle, { color: PROJECT_COLORS.primary }]}>Duyurular & Haberler</Text>
            <Text style={[styles.announcementDesc, { color: PROJECT_COLORS.primary }]}>Biriminizle ilgili güncel gelişmeleri takip edin.</Text>
          </View>
          <FontAwesome6 name="chevron-right" size={14} color={PROJECT_COLORS.primary} />
        </Pressable>
      </View>
      <View style={{ height: 100 }} />
    </ScrollView>
  );
}

function StatusRow({ icon, color, label, value }: any) {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  return (
    <View style={styles.statusRow}>
      <View style={[styles.statusIcon, { backgroundColor: color + '15' }]}>
        <FontAwesome6 name={icon} size={16} color={color} />
      </View>
      <Text style={[styles.statusLabel, { color: theme.text }]}>{label}</Text>
      <Text style={[styles.statusValue, { color: theme.text }]}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  center: { alignItems: 'center', justifyContent: 'center' },
  topSection: { paddingHorizontal: 20, paddingTop: 60, paddingBottom: 10 },
  headerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 25 },
  welcomeBox: { backgroundColor: 'transparent' },
  greetingText: { fontSize: 16, opacity: 0.6, fontWeight: '500' },
  userNameText: { fontSize: 24, fontWeight: '800', letterSpacing: -0.5, marginTop: 4 },
  profileButton: { width: 45, height: 45, borderRadius: 15, alignItems: 'center', justifyContent: 'center', borderWidth: 1 },
  heroCard: { borderRadius: 24, padding: 24, height: 130, justifyContent: 'center', overflow: 'hidden', elevation: 8, shadowColor: PROJECT_COLORS.primary, shadowOffset: { width: 0, height: 10 }, shadowOpacity: 0.2, shadowRadius: 15 },
  heroContent: { backgroundColor: 'transparent', zIndex: 1 },
  heroTitle: { color: '#fff', fontSize: 24, fontWeight: '900', letterSpacing: 1 },
  heroSubtitle: { color: '#fff', fontSize: 13, opacity: 0.8, marginTop: 4 },
  heroIconBox: { position: 'absolute', right: -10, bottom: -10 },
  heroBgIcon: { transform: [{ rotate: '-15deg' }] },
  content: { paddingHorizontal: 20 },
  sectionTitle: { fontSize: 18, fontWeight: '700', marginBottom: 15, marginTop: 25 },
  actionGrid: { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between' },
  actionCard: { width: (width - 55) / 2, padding: 16, borderRadius: 20, marginBottom: 15, borderWidth: 1, alignItems: 'center' },
  actionIcon: { width: 50, height: 50, borderRadius: 15, alignItems: 'center', justifyContent: 'center', marginBottom: 12 },
  actionText: { fontSize: 13, fontWeight: '700' },
  statusSection: { marginTop: 10 },
  infoCard: { borderRadius: 24, borderWidth: 1, padding: 8 },
  statusRow: { flexDirection: 'row', alignItems: 'center', padding: 12 },
  statusIcon: { width: 34, height: 34, borderRadius: 10, alignItems: 'center', justifyContent: 'center', marginRight: 12 },
  statusLabel: { flex: 1, fontSize: 14, fontWeight: '600' },
  statusValue: { fontSize: 16, fontWeight: '800' },
  divider: { height: 1, marginHorizontal: 12 },
  announcementCard: { flexDirection: 'row', alignItems: 'center', padding: 16, borderRadius: 20, marginTop: 20 },
  announcementIcon: { width: 40, height: 40, borderRadius: 12, alignItems: 'center', justifyContent: 'center', marginRight: 16 },
  announcementContent: { flex: 1 },
  announcementTitle: { fontSize: 15, fontWeight: '800' },
  announcementDesc: { fontSize: 11, opacity: 0.7, marginTop: 2 },
});
