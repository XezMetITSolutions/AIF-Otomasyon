import React from 'react';
import { Tabs } from 'expo-router';
import { FontAwesome6 } from '@expo/vector-icons';

import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';

export default function TabLayout() {
  const colorScheme = useColorScheme() ?? 'light';

  return (
    <Tabs
      screenOptions={{
        tabBarActiveTintColor: Colors[colorScheme].tint,
        tabBarInactiveTintColor: Colors[colorScheme].tabIconDefault,
        tabBarStyle: {
          backgroundColor: Colors[colorScheme].background,
          borderTopColor: Colors[colorScheme].border,
        },
        headerShown: false,
      }}>
      <Tabs.Screen
        name="index"
        options={{
          title: 'Ana Sayfa',
          tabBarIcon: ({ color }) => (
            <FontAwesome6 name="house" color={color} size={20} />
          ),
        }}
      />
      <Tabs.Screen
        name="menu"
        options={{
          title: 'Menü',
          tabBarIcon: ({ color }) => (
            <FontAwesome6 name="bars-staggered" color={color} size={20} />
          ),
        }}
      />
      <Tabs.Screen
        name="two"
        options={{
          title: 'Ayarlar',
          tabBarIcon: ({ color }) => (
            <FontAwesome6 name="gear" color={color} size={20} />
          ),
        }}
      />
    </Tabs>
  );
}
