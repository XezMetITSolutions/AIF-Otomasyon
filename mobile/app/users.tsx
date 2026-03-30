import React, { useEffect, useState } from 'react';
import { StyleSheet, FlatList, ActivityIndicator, Pressable, RefreshControl } from 'react-native';
import { Stack, router } from 'expo-router';
import { SymbolView } from 'expo-symbols';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import { fetchUsers } from '@/services/api';

export default function UsersScreen() {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  const [users, setUsers] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const loadUsers = async () => {
    setLoading(true);
    const result = await fetchUsers();
    if (result.success) setUsers(result.users);
    setLoading(false);
  };

  const onRefresh = async () => {
    setRefreshing(true);
    const result = await fetchUsers();
    if (result.success) setUsers(result.users);
    setRefreshing(false);
  };

  useEffect(() => { loadUsers(); }, []);

  return (
    <View style={[styles.container, { backgroundColor: theme.background }]}>
      <Stack.Screen options={{ title: 'Kullanıcı Yönetimi' }} />
      
      {loading && !refreshing ? (
        <View style={styles.center}><ActivityIndicator size="large" color={theme.tint} /></View>
      ) : (
        <FlatList
          data={users}
          keyExtractor={(item) => item.kullanici_id.toString()}
          renderItem={({ item }) => (
            <Pressable style={[styles.userCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
              <View style={[styles.avatar, { backgroundColor: theme.tint + '15' }]}>
                <Text style={[styles.avatarText, { color: theme.tint }]}>
                  {item.ad[0].toUpperCase()}{item.soyad[0].toUpperCase()}
                </Text>
              </View>
              <View style={styles.userInfo}>
                <Text style={styles.userName}>{item.ad} {item.soyad}</Text>
                <Text style={styles.userEmail}>{item.email}</Text>
                <View style={styles.roleBadge}>
                  <Text style={styles.roleText}>{item.rol_adi || 'Kullanıcı'}</Text>
                  {item.byk_adi && <Text style={styles.bykText}> • {item.byk_adi}</Text>}
                </View>
              </View>
              <SymbolView name={{ ios: 'chevron.right', android: 'chevron_right', web: 'chevron_right' } as any} tintColor={theme.tabIconDefault} size={16} />
            </Pressable>
          )}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={theme.tint} />}
          contentContainerStyle={styles.listContent}
          ListEmptyComponent={<Text style={styles.emptyText}>Kullanıcı bulunamadı.</Text>}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  listContent: { padding: 16 },
  userCard: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    borderRadius: 15,
    marginBottom: 12,
    borderWidth: 1,
  },
  avatar: {
    width: 44,
    height: 44,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 15,
  },
  avatarText: { fontWeight: 'bold', fontSize: 16 },
  userInfo: { flex: 1, backgroundColor: 'transparent' },
  userName: { fontSize: 16, fontWeight: '700' },
  userEmail: { fontSize: 13, opacity: 0.6, marginTop: 2 },
  roleBadge: { flexDirection: 'row', alignItems: 'center', marginTop: 4, backgroundColor: 'transparent' },
  roleText: { fontSize: 11, fontWeight: '600', textTransform: 'uppercase', color: '#6366f1' },
  bykText: { fontSize: 11, opacity: 0.5 },
  emptyText: { textAlign: 'center', marginTop: 50, opacity: 0.5 },
});
