import React from 'react';
import { StyleSheet, ScrollView, Pressable, Alert } from 'react-native';
import { SymbolView } from 'expo-symbols';
import { router } from 'expo-router';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import AsyncStorage from '@react-native-async-storage/async-storage';

export default function SettingsScreen() {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];

  const handleLogout = () => {
    Alert.alert(
      'Oturumu Kapat',
      'Çıkış yapmak istediğinize emin misiniz?',
      [
        { text: 'İptal', style: 'cancel' },
        { 
          text: 'Çıkış Yap', 
          style: 'destructive',
          onPress: async () => {
            await AsyncStorage.removeItem('user');
            router.replace('/login');
          }
        },
      ]
    );
  };

  return (
    <ScrollView style={[styles.container, { backgroundColor: theme.background }]}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Ayarlar</Text>
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionLabel}>Hesap</Text>
        <SettingItem 
          icon="person.fill" 
          title="Profil Bilgileri" 
          color="#3b82f6" 
          onPress={() => router.push('/modal?type=profile')}
        />
        <SettingItem 
          icon="bell.fill" 
          title="Bildirimler" 
          color="#f59e0b" 
          onPress={() => router.push('/modal?type=notifications')}
        />
        <SettingItem 
          icon="lock.shield.fill" 
          title="Güvenlik" 
          color="#10b981" 
          onPress={() => router.push('/modal?type=security')}
        />
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionLabel}>Uygulama</Text>
        <SettingItem 
          icon="paintbrush.fill" 
          title="Görünüm" 
          color="#8b5cf6" 
          onPress={() => router.push('/modal?type=appearance')}
        />
        <SettingItem 
          icon="globe" 
          title="Dil" 
          subtitle="Türkçe" 
          color="#ec4899" 
          onPress={() => router.push('/modal?type=language')}
        />
      </View>

      <View style={styles.section}>
        <SettingItem 
          icon="info.circle.fill" 
          title="Hakkında" 
          color="#64748b" 
          onPress={() => router.push('/modal?type=about')}
        />
        <SettingItem 
          icon="rectangle.portrait.and.arrow.right" 
          title="Oturumu Kapat" 
          color="#ef4444" 
          hideChevron
          onPress={handleLogout}
        />
      </View>
      
      <View style={styles.footer}>
        <Text style={styles.version}>Versiyon 1.0.0</Text>
      </View>
    </ScrollView>
  );
}

function SettingItem({ icon, title, subtitle, color, hideChevron, onPress }: any) {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];

  return (
    <Pressable 
      style={[styles.item, { backgroundColor: theme.card, borderColor: theme.border }]}
      onPress={onPress}
    >
      <View style={[styles.iconBox, { backgroundColor: color + '15' }]}>
        <SymbolView name={{ ios: icon, android: 'settings', web: 'settings' } as any} tintColor={color} size={20} />
      </View>
      <View style={styles.itemContent}>
        <Text style={styles.itemTitle}>{title}</Text>
        {subtitle && <Text style={styles.itemSubtitle}>{subtitle}</Text>}
      </View>
      {!hideChevron && (
        <SymbolView 
          name={{ ios: 'chevron.right', android: 'chevron_right', web: 'chevron_right' } as any} 
          tintColor={theme.tabIconDefault} 
          size={16} 
        />
      )}
    </Pressable>
  );
}

const styles = StyleSheet.create({
  container: {
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
    marginBottom: 10,
    marginLeft: 5,
  },
  item: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 14,
    borderRadius: 16,
    marginBottom: 8,
    borderWidth: 1,
  },
  iconBox: {
    width: 36,
    height: 36,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  itemContent: {
    flex: 1,
    backgroundColor: 'transparent',
  },
  itemTitle: {
    fontSize: 16,
    fontWeight: '500',
  },
  itemSubtitle: {
    fontSize: 12,
    opacity: 0.5,
    marginTop: 2,
  },
  footer: {
    padding: 20,
    alignItems: 'center',
    backgroundColor: 'transparent',
  },
  version: {
    fontSize: 12,
    opacity: 0.4,
  },
});

