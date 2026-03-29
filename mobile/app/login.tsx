import React, { useState } from 'react';
import { StyleSheet, TextInput, Pressable, Alert, KeyboardAvoidingView, Platform, Dimensions } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { SymbolView } from 'expo-symbols';

import { Text, View } from '@/components/Themed';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import { login } from '@/services/api';

const { width } = Dimensions.get('window');

export default function LoginScreen() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [rememberMe, setRememberMe] = useState(false);
  const [loading, setLoading] = useState(false);
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];

  const handleLogin = async () => {
    if (!email || !password) {
      Alert.alert('Hata', 'Lütfen tüm alanları doldurun.');
      return;
    }

    setLoading(true);
    const result = await login(email, password);
    setLoading(false);

    if (result.success) {
      router.replace('/(tabs)');
    } else {
      Alert.alert('Hata', result.message || 'Giriş başarısız.');
    }
  };

  return (
    <KeyboardAvoidingView 
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      style={styles.container}
    >
      <LinearGradient
        colors={colorScheme === 'dark' ? ['#0f172a', '#1e293b'] : ['#6366f1', '#4f46e5']}
        style={styles.gradient}
      >
        <View style={[styles.card, { backgroundColor: theme.card, borderColor: theme.border }]}>
          <Text style={[styles.title, { color: theme.text }]}>AİFNET Giriş</Text>
          <Text style={[styles.subtitle, { color: theme.text, opacity: 0.6 }]}>
            Otomasyon sistemine hoş geldiniz.
          </Text>

          <View style={styles.inputContainer}>
            <View style={[styles.inputWrapper, { borderColor: theme.border, backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f8fafc' }]}>
              <SymbolView name={{ ios: 'envelope.fill', android: 'email', web: 'email' } as any} tintColor={theme.tabIconDefault} size={20} />
              <TextInput
                style={[styles.input, { color: theme.text }]}
                placeholder="E-posta"
                placeholderTextColor={colorScheme === 'dark' ? '#64748b' : '#94a3b8'}
                value={email}
                onChangeText={setEmail}
                autoCapitalize="none"
                keyboardType="email-address"
              />
            </View>

            <View style={[styles.inputWrapper, { borderColor: theme.border, backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f8fafc' }]}>
              <SymbolView name={{ ios: 'lock.fill', android: 'lock', web: 'lock' } as any} tintColor={theme.tabIconDefault} size={20} />
              <TextInput
                style={[styles.input, { color: theme.text }]}
                placeholder="Şifre"
                placeholderTextColor={colorScheme === 'dark' ? '#64748b' : '#94a3b8'}
                value={password}
                onChangeText={setPassword}
                secureTextEntry
              />
            </View>
          </View>

          <View style={styles.optionsRow}>
            <Pressable style={styles.rememberRow} onPress={() => setRememberMe(!rememberMe)}>
              <View style={[styles.checkbox, { borderColor: theme.tint, backgroundColor: rememberMe ? theme.tint : 'transparent' }]}>
                {rememberMe && <SymbolView name={{ ios: 'checkmark', android: 'check', web: 'check' } as any} tintColor="#fff" size={14} />}
              </View>
              <Text style={[styles.rememberText, { color: theme.text }]}>Beni Hatırla</Text>
            </Pressable>
            <Pressable>
              <Text style={[styles.forgotText, { color: theme.tint }]}>Şifremi Unuttum</Text>
            </Pressable>
          </View>

          <Pressable 
            style={[styles.button, { backgroundColor: theme.tint }]} 
            onPress={handleLogin}
            disabled={loading}
          >
            <Text style={styles.buttonText}>
              {loading ? 'Giriş Yapılıyor...' : 'Giriş Yap'}
            </Text>
          </Pressable>

          <Text style={[styles.footer, { color: theme.text, opacity: 0.4 }]}>© 2026 AİF Otomasyon</Text>
        </View>
      </LinearGradient>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  gradient: {
    flex: 1,
    justifyContent: 'center',
    padding: 20,
  },
  card: {
    padding: 30,
    borderRadius: 30,
    borderWidth: 1,
    elevation: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.25,
    shadowRadius: 20,
    alignItems: 'center',
    width: '100%',
  },
  title: {
    fontSize: 28,
    fontWeight: '800',
    marginBottom: 10,
  },
  subtitle: {
    fontSize: 14,
    marginBottom: 30,
    textAlign: 'center',
  },
  inputContainer: {
    width: '100%',
    backgroundColor: 'transparent',
    marginBottom: 10,
  },
  inputWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    height: 55,
    borderWidth: 1,
    borderRadius: 15,
    paddingHorizontal: 15,
    marginBottom: 15,
  },
  input: {
    flex: 1,
    height: '100%',
    marginLeft: 10,
    fontSize: 16,
  },
  optionsRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    width: '100%',
    marginBottom: 30,
    backgroundColor: 'transparent',
  },
  rememberRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'transparent',
  },
  checkbox: {
    width: 20,
    height: 20,
    borderRadius: 6,
    borderWidth: 2,
    marginRight: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },
  rememberText: {
    fontSize: 14,
    opacity: 0.8,
  },
  forgotText: {
    fontSize: 14,
    fontWeight: '600',
  },
  button: {
    width: '100%',
    height: 55,
    borderRadius: 15,
    justifyContent: 'center',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 10,
  },
  buttonText: {
    color: '#ffffff',
    fontSize: 18,
    fontWeight: '700',
  },
  footer: {
    marginTop: 40,
    fontSize: 12,
  },
});
