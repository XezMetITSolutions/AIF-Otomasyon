import { DarkTheme, DefaultTheme, ThemeProvider } from '@react-navigation/native';
import { useFonts } from 'expo-font';
import { Stack, router, useSegments } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { useEffect, useState } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';

import { useColorScheme } from '@/components/useColorScheme';

export {
  // Catch any errors thrown by the Layout component.
  ErrorBoundary,
} from 'expo-router';

export const unstable_settings = {
  // Ensure that reloading on `/modal` keeps a back button present.
  initialRouteName: 'login',
};

// Prevent the splash screen from auto-hiding before asset loading is complete.
SplashScreen.preventAutoHideAsync();

export default function RootLayout() {
  const [loaded, error] = useFonts({
    SpaceMono: require('../assets/fonts/SpaceMono-Regular.ttf'),
  });

  // Expo Router uses Error Boundaries to catch errors in the navigation tree.
  useEffect(() => {
    if (error) throw error;
  }, [error]);

  useEffect(() => {
    if (loaded) {
      SplashScreen.hideAsync();
    }
  }, [loaded]);

  if (!loaded) {
    return null;
  }

  return <RootLayoutNav />;
}

function RootLayoutNav() {
  const colorScheme = useColorScheme();
  const segments = useSegments();
  const [isAuthChecking, setIsAuthChecking] = useState(true);

  useEffect(() => {
    const checkAuth = async () => {
      const user = await AsyncStorage.getItem('user');
      const inAuthGroup = segments[0] === '(tabs)';

      if (!user && inAuthGroup) {
        // Redirect to the login page if not authenticated and trying to access tabs
        router.replace('/login');
      } else if (user && segments[0] === 'login') {
        // Redirect to tabs if already authenticated and on login page
        router.replace('/(tabs)');
      }
      setIsAuthChecking(false);
    };

    if (!isAuthChecking) return;
    checkAuth();
  }, [segments, isAuthChecking]);

  return (
    <ThemeProvider value={colorScheme === 'dark' ? DarkTheme : DefaultTheme}>
      <Stack initialRouteName="login">
        <Stack.Screen name="login" options={{ headerShown: false }} />
        <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
        <Stack.Screen name="modal" options={{ presentation: 'modal' }} />
        <Stack.Screen name="etkinlikler" options={{ title: 'Etkinlikler' }} />
        <Stack.Screen name="meetings" options={{ title: 'Toplantılar' }} />
        <Stack.Screen name="meeting-detail" options={{ title: 'Toplantı Detayı' }} />
        <Stack.Screen name="projeler" options={{ title: 'Projeler' }} />
        <Stack.Screen name="sube-ziyaretleri" options={{ title: 'Şube Ziyaretleri' }} />
        <Stack.Screen name="tasks" options={{ title: 'Görevler' }} />
        <Stack.Screen name="iade-talebi" options={{ title: 'İade Talebi Formu' }} />
      </Stack>
    </ThemeProvider>
  );
}
