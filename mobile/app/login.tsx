import React, { useState, useEffect, useRef } from 'react';
import { 
  StyleSheet, 
  TextInput, 
  Pressable, 
  Alert, 
  KeyboardAvoidingView, 
  Platform, 
  Dimensions, 
  Image, 
  Animated, 
  ActivityIndicator,
  View,
  Text,
  SafeAreaView,
  StatusBar,
  ScrollView
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { FontAwesome6 } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';

import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import { login } from '@/services/api';

const { width } = Dimensions.get('window');

export default function LoginScreen() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const colorScheme = useColorScheme() ?? 'light';
  const theme = Colors[colorScheme];

  const fadeAnim = useRef(new Animated.Value(0)).current;
  const slideAnim = useRef(new Animated.Value(20)).current;

  useEffect(() => {
    Animated.parallel([
      Animated.timing(fadeAnim, {
        toValue: 1,
        duration: 800,
        useNativeDriver: true,
      }),
      Animated.timing(slideAnim, {
        toValue: 0,
        duration: 800,
        useNativeDriver: true,
      }),
    ]).start();
  }, []);

  const handleLogin = async () => {
    if (!email || !password) {
      Alert.alert('Hata', 'Lütfen tüm alanları doldurun.');
      return;
    }

    setLoading(true);
    try {
      const result = await login(email, password);
      if (result.success) {
        await AsyncStorage.setItem('user', JSON.stringify(result.user));
        router.replace('/(tabs)');
      } else {
        Alert.alert('Hata', result.message || 'Giriş başarısız.');
      }
    } catch (error) {
      Alert.alert('Hata', 'Sunucuya bağlanılamadı.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <StatusBar barStyle={colorScheme === 'dark' ? 'light-content' : 'dark-content'} />
      <LinearGradient
        colors={colorScheme === 'dark' ? ['#020617', '#0f172a'] : ['#f8fafc', '#f1f5f9']}
        style={styles.container}
      >
        <SafeAreaView style={styles.safeArea}>
          <KeyboardAvoidingView 
            behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
            style={styles.keyboardView}
          >
            <ScrollView 
              contentContainerStyle={styles.scrollContent}
              showsVerticalScrollIndicator={false}
              keyboardShouldPersistTaps="handled"
            >
              <Animated.View style={[styles.content, { opacity: fadeAnim, transform: [{ translateY: slideAnim }] }]}>
              <View style={styles.headerSection}>
                <View style={styles.logoContainer}>
                  <Image 
                    source={require('../assets/images/logo.png')} 
                    style={styles.logo}
                    resizeMode="contain"
                  />
                </View>
                <Text style={[styles.title, { color: theme.text }]}>Hoş Geldiniz</Text>
                <Text style={[styles.subtitle, { color: theme.text, opacity: 0.5 }]}>Devam etmek için giriş yapın</Text>
              </View>

              <View style={[styles.formCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
                <View style={styles.inputGroup}>
                  <Text style={[styles.inputLabel, { color: theme.text }]}>E-Posta Adresi</Text>
                  <View style={[styles.inputWrapper, { backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f1f5f9' }]}>
                    <FontAwesome6 name="envelope" size={16} color={theme.tabIconDefault} style={styles.inputIcon} />
                    <TextInput
                      style={[styles.input, { color: theme.text }]}
                      placeholder="ornek@aifnet.com"
                      placeholderTextColor="#94a3b8"
                      value={email}
                      onChangeText={(text) => setEmail(text)}
                      autoCapitalize="none"
                      keyboardType="email-address"
                      underlineColorAndroid="transparent"
                      textContentType="emailAddress"
                    />
                  </View>
                </View>

                <View style={[styles.inputGroup, { marginTop: 20 }]}>
                  <View style={styles.labelRow}>
                    <Text style={[styles.inputLabel, { color: theme.text }]}>Şifre</Text>
                    <Pressable>
                      <Text style={[styles.forgotLink, { color: theme.tint }]}>Şifremi Unuttum</Text>
                    </Pressable>
                  </View>
                  <View style={[styles.inputWrapper, { backgroundColor: colorScheme === 'dark' ? '#1e293b' : '#f1f5f9' }]}>
                    <FontAwesome6 name="lock" size={16} color={theme.tabIconDefault} style={styles.inputIcon} />
                    <TextInput
                      style={[styles.input, { color: theme.text }]}
                      placeholder="••••••••"
                      placeholderTextColor="#94a3b8"
                      value={password}
                      onChangeText={(text) => setPassword(text)}
                      secureTextEntry={!showPassword}
                      underlineColorAndroid="transparent"
                      textContentType="password"
                      autoCorrect={false}
                      autoCapitalize="none"
                      onSubmitEditing={handleLogin}
                      returnKeyType="done"
                    />
                    <Pressable onPress={() => setShowPassword(!showPassword)} style={styles.eyeIcon}>
                      <FontAwesome6 name={showPassword ? "eye-slash" : "eye"} size={16} color={theme.tabIconDefault} />
                    </Pressable>
                  </View>
                </View>

                <Pressable 
                  onPress={handleLogin}
                  disabled={loading}
                  style={({ pressed }) => [
                    styles.loginButton,
                    { backgroundColor: theme.tint, opacity: (pressed || loading) ? 0.8 : 1 }
                  ]}
                >
                  {loading ? (
                    <ActivityIndicator color="#ffffff" />
                  ) : (
                    <Text style={styles.loginButtonText}>Giriş Yap</Text>
                  )}
                </Pressable>

                <View style={styles.divider}>
                  <View style={[styles.dividerLine, { backgroundColor: theme.border, opacity: 0.1 }]} />
                  <Text style={[styles.dividerText, { color: theme.text, opacity: 0.3 }]}>VEYA</Text>
                  <View style={[styles.dividerLine, { backgroundColor: theme.border, opacity: 0.1 }]} />
                </View>

                <Pressable 
                  style={[styles.biometricButton, { borderColor: theme.border }]}
                  onPress={() => Alert.alert('Bilgi', 'Biyometrik giriş yakında aktif edilecek.')}
                >
                  <FontAwesome6 name="fingerprint" size={20} color={theme.tint} />
                  <Text style={[styles.biometricText, { color: theme.text }]}>Parmak İzi ile Giriş</Text>
                </Pressable>
              </View>

              <View style={styles.footer}>
                <Text style={[styles.footerText, { color: theme.text, opacity: 0.6 }]}>
                  Bir hesabınız yok mu? <Text style={{ color: theme.tint, fontWeight: '700' }}>İletişime Geçin</Text>
                </Text>
              </View>
            </Animated.View>
          </ScrollView>
          </KeyboardAvoidingView>
        </SafeAreaView>
      </LinearGradient>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  safeArea: { flex: 1 },
  keyboardView: { flex: 1 },
  scrollContent: {
    flexGrow: 1,
    justifyContent: 'center',
  },
  content: { 
    paddingHorizontal: 24,
    paddingVertical: 40,
  },
  headerSection: {
    alignItems: 'center',
    marginBottom: 40,
  },
  logoContainer: {
    width: 100,
    height: 100,
    borderRadius: 24,
    backgroundColor: '#ffffff',
    justifyContent: 'center',
    alignItems: 'center',
    elevation: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.1,
    shadowRadius: 10,
    marginBottom: 24
  },
  logo: {
    width: 70,
    height: 70,
  },
  title: {
    fontSize: 28,
    fontWeight: '800',
    letterSpacing: -0.5,
    marginBottom: 8
  },
  subtitle: {
    fontSize: 16,
  },
  formCard: {
    borderRadius: 32,
    padding: 24,
    borderWidth: 1,
    elevation: 4,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.05,
    shadowRadius: 20,
  },
  inputGroup: {},
  labelRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  inputLabel: {
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 8,
    marginLeft: 4
  },
  forgotLink: {
    fontSize: 13,
    fontWeight: '600',
  },
  inputWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    height: 56,
    borderRadius: 16,
    paddingHorizontal: 16,
  },
  inputIcon: {
    marginRight: 12,
  },
  input: {
    flex: 1,
    fontSize: 16,
    fontWeight: '500'
  },
  eyeIcon: {
    padding: 8
  },
  loginButton: {
    height: 56,
    borderRadius: 16,
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: 32,
    elevation: 4,
  },
  loginButtonText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '700'
  },
  divider: {
    flexDirection: 'row',
    alignItems: 'center',
    marginVertical: 24,
  },
  dividerLine: {
    flex: 1,
    height: 1,
  },
  dividerText: {
    fontSize: 12,
    fontWeight: '700',
    marginHorizontal: 16,
  },
  biometricButton: {
    flexDirection: 'row',
    height: 56,
    borderRadius: 16,
    borderWidth: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  biometricText: {
    fontSize: 15,
    fontWeight: '600',
    marginLeft: 12
  },
  footer: {
    marginTop: 32,
    alignItems: 'center',
  },
  footerText: {
    fontSize: 14,
  }
});
