import React, { useEffect, useState } from 'react';
import { StyleSheet, ScrollView, Dimensions, Pressable, ActivityIndicator, RefreshControl } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { BlurView } from 'expo-blur';
import { SymbolView } from 'expo-symbols';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import { fetchStats } from '@/services/api';

const { width } = Dimensions.get('window');

export default function DashboardScreen() {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [stats, setStats] = useState<any>(null);
  const [activities, setActivities] = useState<any[]>([]);

  const loadData = async () => {
    setLoading(true);
    const result = await fetchStats();
    if (result.success) {
      setStats(result.stats);
      setActivities(result.activities || []);
    }
    setLoading(false);
  };

  const onRefresh = async () => {
    setRefreshing(true);
    const result = await fetchStats();
    if (result.success) {
      setStats(result.stats);
      setActivities(result.activities || []);
    }
    setRefreshing(false);
  };

  useEffect(() => {
    loadData();
  }, []);

  if (loading && !refreshing) {
    return (
      <View style={[styles.container, styles.center, { backgroundColor: theme.background }]}>
        <ActivityIndicator size="large" color={theme.tint} />
      </View>
    );
  }

  return (
    <ScrollView 
      style={[styles.container, { backgroundColor: theme.background }]}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={theme.tint} />}
    >
      <LinearGradient
        colors={colorScheme === 'dark' ? ['#1e293b', '#0f172a'] : ['#6366f1', '#4f46e5']}
        style={styles.header}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
      >
        <View style={styles.headerContent}>
          <Text style={styles.greeting}>Hoş Geldiniz,</Text>
          <Text style={styles.userName}>AİF Paneli</Text>
        </View>
        
        <BlurView intensity={70} tint={colorScheme === 'dark' ? 'dark' : 'light'} style={styles.statsContainer}>
          <View style={styles.statItem}>
            <Text style={styles.statValue}>{stats?.toplam_toplanti || 0}</Text>
            <Text style={styles.statLabel}>Planlanan</Text>
          </View>
          <View style={styles.divider} />
          <View style={styles.statItem}>
            <Text style={styles.statValue}>{stats?.bekleyen_izin || 0}</Text>
            <Text style={styles.statLabel}>Bekleyen İzin</Text>
          </View>
        </BlurView>
      </LinearGradient>

      <View style={styles.content}>
        <Text style={styles.sectionTitle}>Hızlı İstatistikler</Text>
        <View style={styles.grid}>
          <ServiceCard 
            title="Kullanıcılar" 
            value={stats?.toplam_kullanici || 0}
            icon="person.3.fill" 
            color="#ec4899" 
            onPress={() => {}} 
          />
          <ServiceCard 
            title="Aktif BYK" 
            value={stats?.toplam_byk || 0}
            icon="building.2.fill" 
            color="#8b5cf6" 
            onPress={() => {}} 
          />
          <ServiceCard 
            title="Etkinlikler" 
            value={stats?.toplam_etkinlik || 0}
            icon="calendar" 
            color="#06b6d4" 
            onPress={() => {}} 
          />
          <ServiceCard 
            title="Harcamalar" 
            value={stats?.bekleyen_harcama || 0}
            icon="creditcard.fill" 
            color="#f97316" 
            onPress={() => {}} 
          />
        </View>

        <Text style={styles.sectionTitle}>Son Aktiviteler</Text>
        {activities.length > 0 ? (
          activities.map((item, index) => (
            <ActivityItem 
              key={index}
              title={item.baslik} 
              time={new Date(item.tarih).toLocaleDateString('tr-TR')} 
              description={`${item.kullanici} tarafından oluşturuldu.`}
            />
          ))
        ) : (
          <Text style={styles.noData}>Henüz aktivite bulunmuyor.</Text>
        )}
      </View>
    </ScrollView>
  );
}

function ServiceCard({ title, value, icon, color, onPress }: any) {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];

  return (
    <Pressable style={[styles.card, { backgroundColor: theme.card, borderColor: theme.border }]} onPress={onPress}>
      <View style={[styles.iconContainer, { backgroundColor: color + '15' }]}>
        <SymbolView name={{ ios: icon, android: 'description', web: 'description' } as any} tintColor={color} size={24} />
      </View>
      <View style={styles.cardContent}>
        <Text style={styles.cardValue}>{value}</Text>
        <Text style={styles.cardTitle}>{title}</Text>
      </View>
    </Pressable>
  );
}

function ActivityItem({ title, time, description }: any) {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];

  return (
    <View style={[styles.activityItem, { backgroundColor: theme.card, borderColor: theme.border }]}>
      <View style={styles.activityHeader}>
        <Text style={styles.activityTitle}>{title}</Text>
        <Text style={styles.activityTime}>{time}</Text>
      </View>
      <Text style={styles.activityDescription}>{description}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  center: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  header: {
    paddingTop: 60,
    paddingHorizontal: 20,
    paddingBottom: 40,
    borderBottomLeftRadius: 30,
    borderBottomRightRadius: 30,
  },
  headerContent: {
    backgroundColor: 'transparent',
    marginBottom: 30,
  },
  greeting: {
    fontSize: 18,
    color: '#e2e8f0',
    opacity: 0.9,
  },
  userName: {
    fontSize: 32,
    fontWeight: '800',
    color: '#ffffff',
  },
  statsContainer: {
    flexDirection: 'row',
    borderRadius: 20,
    padding: 20,
    overflow: 'hidden',
  },
  statItem: {
    flex: 1,
    alignItems: 'center',
    backgroundColor: 'transparent',
  },
  statValue: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#ffffff',
  },
  statLabel: {
    fontSize: 12,
    color: '#ffffff',
    opacity: 0.8,
  },
  divider: {
    width: 1,
    height: '100%',
    backgroundColor: 'rgba(255,255,255,0.2)',
  },
  content: {
    padding: 20,
    backgroundColor: 'transparent',
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: '700',
    marginBottom: 15,
    marginTop: 10,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    backgroundColor: 'transparent',
  },
  card: {
    width: (width - 60) / 2,
    padding: 20,
    borderRadius: 20,
    marginBottom: 20,
    borderWidth: 1,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.1,
    shadowRadius: 10,
    elevation: 3,
  },
  cardValue: {
    fontSize: 24,
    fontWeight: 'bold',
  },
  cardContent: {
    backgroundColor: 'transparent',
  },
  iconContainer: {
    width: 48,
    height: 48,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  cardTitle: {
    fontSize: 14,
    fontWeight: '600',
    opacity: 0.7,
  },
  activityItem: {
    padding: 16,
    borderRadius: 15,
    marginBottom: 12,
    borderWidth: 1,
  },
  activityHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 4,
    backgroundColor: 'transparent',
  },
  activityTitle: {
    fontSize: 15,
    fontWeight: '600',
  },
  activityTime: {
    fontSize: 12,
    opacity: 0.5,
  },
  activityDescription: {
    fontSize: 14,
    opacity: 0.7,
    lineHeight: 20,
  },
  noData: {
    textAlign: 'center',
    opacity: 0.5,
    marginTop: 20,
  },
});
