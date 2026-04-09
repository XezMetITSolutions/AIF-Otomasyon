import { StatusBar } from 'expo-status-bar';
import { Platform, StyleSheet, ScrollView, Pressable, Switch, TextInput, Alert, ActivityIndicator } from 'react-native';
import { useLocalSearchParams, router } from 'expo-router';
import React, { useEffect, useState } from 'react';
import { FontAwesome6 } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import { updateProfile, changePassword } from '@/services/api';

export default function ModalScreen() {
  const { type } = useLocalSearchParams<{ type: string }>();
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  
  const [user, setUser] = useState<any>(null);
  const [loading, setLoading] = useState(false);
  
  // Profile states
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  
  // Security states
  const [oldPassword, setOldPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');

  useEffect(() => {
    const loadUser = async () => {
      const userData = await AsyncStorage.getItem('user');
      if (userData) {
        const u = JSON.parse(userData);
        setUser(u);
        setName(u.name || '');
        setEmail(u.email || '');
      }
    };
    loadUser();
  }, []);

  const handleUpdateProfile = async () => {
    if (!user?.id) {
      console.log('User ID missing', user);
      Alert.alert('Hata', 'Kullanıcı ID bulunamadı.');
      return;
    }
    setLoading(true);
    console.log('Updating profile for ID:', user.id, { name, email });
    const result = await updateProfile(user.id, name, email);
    console.log('Update Result:', result);
    setLoading(false);
    
    if (result.success) {
      const updatedUser = { ...user, name, email };
      await AsyncStorage.setItem('user', JSON.stringify(updatedUser));
      setUser(updatedUser);
      Alert.alert('Başarılı', 'Profil bilgileriniz güncellendi.');
    } else {
      Alert.alert('Hata', result.message);
    }
  };

  const handleChangePassword = async () => {
    if (!user?.id) return;
    if (newPassword !== confirmPassword) {
      Alert.alert('Hata', 'Yeni şifreler eşleşmiyor.');
      return;
    }
    
    setLoading(true);
    console.log('Changing password for ID:', user.id);
    const result = await changePassword(user.id, oldPassword, newPassword);
    console.log('Change Password Result:', result);
    setLoading(false);
    
    if (result.success) {
      setOldPassword('');
      setNewPassword('');
      setConfirmPassword('');
      Alert.alert('Başarılı', 'Şifreniz güncellendi.');
    } else {
      Alert.alert('Hata', result.message);
    }
  };

  const renderContent = () => {
    switch (type) {
      case 'profile':
        return (
          <View style={styles.content}>
            <View style={styles.profileHeader}>
              <View style={[styles.avatar, { backgroundColor: theme.tint + '20' }]}>
                <Text style={[styles.avatarText, { color: theme.tint }]}>
                  {name?.charAt(0) || 'U'}
                </Text>
              </View>
              <Text style={styles.profileName}>{name || 'Kullanıcı'}</Text>
              <Text style={styles.profileRole}>{user?.role === 'super_admin' ? 'Yönetici' : 'Üye'}</Text>
            </View>

            <View style={styles.infoSection}>
              <Text style={styles.inputLabel}>Ad Soyad</Text>
              <TextInput 
                style={[styles.input, { color: theme.text, borderColor: theme.border, backgroundColor: theme.card }]} 
                value={name}
                onChangeText={setName}
              />
              <Text style={styles.inputLabel}>E-posta</Text>
              <TextInput 
                style={[styles.input, { color: theme.text, borderColor: theme.border, backgroundColor: theme.card }]} 
                value={email}
                onChangeText={setEmail}
                keyboardType="email-address"
                autoCapitalize="none"
              />
            </View>

            <Pressable 
              style={[styles.actionButton, { backgroundColor: theme.tint }]} 
              onPress={handleUpdateProfile}
              disabled={loading}
            >
              {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.actionButtonText}>Profil Güncelle</Text>}
            </Pressable>
          </View>
        );

      case 'notifications':
        return (
          <View style={styles.content}>
            <Text style={styles.sectionTitle}>Bildirim Tercihleri</Text>
            <ToggleRow label="Anlık Bildirimler" value={true} />
            <ToggleRow label="E-posta Bildirimleri" value={false} />
            <ToggleRow label="Duyuru Bildirimleri" value={true} />
            <ToggleRow label="Görev Atamaları" value={true} />
          </View>
        );

      case 'security':
        return (
          <View style={styles.content}>
            <Text style={styles.sectionTitle}>Şifre Değiştir</Text>
            <TextInput 
              style={[styles.input, { color: theme.text, borderColor: theme.border, backgroundColor: theme.card }]} 
              placeholder="Mevcut Şifre" 
              placeholderTextColor="#94a3b8"
              secureTextEntry
              value={oldPassword}
              onChangeText={setOldPassword}
            />
            <TextInput 
              style={[styles.input, { color: theme.text, borderColor: theme.border, backgroundColor: theme.card }]} 
              placeholder="Yeni Şifre" 
              placeholderTextColor="#94a3b8"
              secureTextEntry
              value={newPassword}
              onChangeText={setNewPassword}
            />
            <TextInput 
              style={[styles.input, { color: theme.text, borderColor: theme.border, backgroundColor: theme.card }]} 
              placeholder="Yeni Şifre (Tekrar)" 
              placeholderTextColor="#94a3b8"
              secureTextEntry
              value={confirmPassword}
              onChangeText={setConfirmPassword}
            />
            <Pressable 
              style={[styles.actionButton, { backgroundColor: theme.tint, marginTop: 10 }]}
              onPress={handleChangePassword}
              disabled={loading}
            >
              {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.actionButtonText}>Şifreyi Güncelle</Text>}
            </Pressable>
          </View>
        );

      case 'appearance':
        return (
          <View style={styles.content}>
            <Text style={styles.sectionTitle}>Tema Seçimi</Text>
            <SelectionRow label="Sistem Varsayılanı" selected={true} />
            <SelectionRow label="Açık Tema" selected={false} />
            <SelectionRow label="Koyu Tema" selected={false} />
          </View>
        );

      case 'language':
        return (
          <View style={styles.content}>
            <Text style={styles.sectionTitle}>Dil Seçimi</Text>
            <SelectionRow label="Türkçe" selected={true} />
            <SelectionRow label="Deutsch" selected={false} />
            <SelectionRow label="English" selected={false} />
          </View>
        );

      case 'about':
        return (
          <View style={styles.content}>
            <View style={styles.aboutHeader}>
              <View style={[styles.logoPlaceholder, { backgroundColor: theme.tint }]}>
                <FontAwesome6 name="shield-halved" size={40} color="#fff" />
              </View>
              <Text style={styles.aboutTitle}>AİFNET</Text>
              <Text style={styles.aboutVersion}>Versiyon 1.0.0 (Build 42)</Text>
            </View>
            <View style={styles.aboutBody}>
              <Text style={[styles.aboutText, { color: theme.text }]}>
                AİFNET, Avusturya İslam Federasyonu birimler arası koordinasyon ve iş akış yönetimi için geliştirilmiş resmi mobil uygulamadır.
              </Text>
              <Text style={[styles.copyright, { color: theme.text, opacity: 0.5 }]}>
                © 2026 AİF Bilişim Başkanlığı. Tüm hakları saklıdır.
              </Text>
            </View>
          </View>
        );

      default:
        return (
          <View style={styles.container}>
            <Text style={styles.title}>Bilinmeyen Ayar</Text>
          </View>
        );
    }
  };

  const getTitle = () => {
    switch (type) {
      case 'profile': return 'Profil Bilgileri';
      case 'notifications': return 'Bildirimler';
      case 'security': return 'Güvenlik';
      case 'appearance': return 'Görünüm';
      case 'language': return 'Dil Seçimi';
      case 'about': return 'Hakkında';
      default: return 'Ayarlar';
    }
  };

  return (
    <View style={[styles.container, { backgroundColor: theme.background }]}>
      <View style={styles.header}>
        <Text style={[styles.title, { color: theme.text }]}>{getTitle()}</Text>
        <Pressable onPress={() => router.back()} style={styles.closeButton}>
          <FontAwesome6 name="xmark" size={20} color={theme.text} />
        </Pressable>
      </View>
      <ScrollView bounces={false}>
        {renderContent()}
      </ScrollView>
      <StatusBar style={Platform.OS === 'ios' ? 'light' : 'auto'} />
    </View>
  );
}

function InfoRow({ label, value }: { label: string; value: string }) {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  return (
    <View style={[styles.infoRow, { borderBottomColor: theme.border }]}>
      <Text style={[styles.infoLabel, { color: theme.text, opacity: 0.6 }]}>{label}</Text>
      <Text style={[styles.infoValue, { color: theme.text }]}>{value}</Text>
    </View>
  );
}

function ToggleRow({ label, value }: { label: string; value: boolean }) {
  const [isEnabled, setIsEnabled] = useState(value);
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  return (
    <View style={[styles.toggleRow, { borderBottomColor: theme.border }]}>
      <Text style={[styles.toggleLabel, { color: theme.text }]}>{label}</Text>
      <Switch
        trackColor={{ false: '#767577', true: theme.tint + '80' }}
        thumbColor={isEnabled ? theme.tint : '#f4f3f4'}
        onValueChange={() => setIsEnabled(!isEnabled)}
        value={isEnabled}
      />
    </View>
  );
}

function SelectionRow({ label, selected }: { label: string; selected: boolean }) {
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];
  return (
    <Pressable style={[styles.selectionRow, { borderBottomColor: theme.border }]}>
      <Text style={[styles.selectionLabel, { color: theme.text, fontWeight: selected ? '700' : '400' }]}>{label}</Text>
      {selected && <FontAwesome6 name="check" size={16} color={theme.tint} />}
    </Pressable>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 20,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: '#ccc',
  },
  title: {
    fontSize: 18,
    fontWeight: '800',
  },
  closeButton: {
    position: 'absolute',
    right: 20,
    padding: 5,
  },
  content: {
    padding: 20,
    backgroundColor: 'transparent',
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: '700',
    marginBottom: 20,
  },
  profileHeader: {
    alignItems: 'center',
    marginBottom: 30,
    backgroundColor: 'transparent',
  },
  avatar: {
    width: 80,
    height: 80,
    borderRadius: 40,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 15,
  },
  avatarText: {
    fontSize: 32,
    fontWeight: '800',
  },
  profileName: {
    fontSize: 22,
    fontWeight: '700',
  },
  profileRole: {
    fontSize: 14,
    opacity: 0.5,
    marginTop: 4,
  },
  infoSection: {
    marginBottom: 30,
    backgroundColor: 'transparent',
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 15,
    borderBottomWidth: StyleSheet.hairlineWidth,
  },
  infoLabel: {
    fontSize: 15,
  },
  infoValue: {
    fontSize: 15,
    fontWeight: '600',
  },
  inputLabel: {
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 8,
    opacity: 0.7,
  },
  actionButton: {
    height: 55,
    borderRadius: 15,
    alignItems: 'center',
    justifyContent: 'center',
    shadowOpacity: 0.2,
    shadowRadius: 10,
    elevation: 5,
  },
  actionButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '700',
  },
  toggleRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 15,
    borderBottomWidth: StyleSheet.hairlineWidth,
  },
  toggleLabel: {
    fontSize: 16,
  },
  selectionRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 18,
    borderBottomWidth: StyleSheet.hairlineWidth,
  },
  selectionLabel: {
    fontSize: 16,
  },
  input: {
    height: 55,
    borderWidth: 1,
    borderRadius: 12,
    paddingHorizontal: 15,
    marginBottom: 15,
    fontSize: 16,
  },
  aboutHeader: {
    alignItems: 'center',
    marginTop: 20,
    marginBottom: 40,
    backgroundColor: 'transparent',
  },
  logoPlaceholder: {
    width: 100,
    height: 100,
    borderRadius: 25,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 20,
    shadowOpacity: 0.3,
    shadowRadius: 15,
    elevation: 10,
  },
  aboutTitle: {
    fontSize: 24,
    fontWeight: '800',
  },
  aboutVersion: {
    fontSize: 14,
    opacity: 0.5,
    marginTop: 5,
  },
  aboutBody: {
    backgroundColor: 'transparent',
  },
  aboutText: {
    textAlign: 'center',
    fontSize: 16,
    lineHeight: 24,
    marginBottom: 40,
  },
  copyright: {
    textAlign: 'center',
    fontSize: 12,
  },
});
