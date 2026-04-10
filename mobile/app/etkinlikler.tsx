import { StyleSheet, ActivityIndicator, RefreshControl, SectionList, Linking, Pressable } from 'react-native';
...
  const openMap = (address: string) => {
    if (!address) return;
    const url = Platform.select({
      ios: `maps:0,0?q=${encodeURIComponent(address)}`,
      android: `geo:0,0?q=${encodeURIComponent(address)}`,
    }) || `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`;
    
    Linking.openURL(url).catch(err => console.error('Maps failed:', err));
  };

  return (
    <View style={[styles.container, { backgroundColor: colorScheme === 'light' ? PROJECT_COLORS.bgSoft : theme.background }]}>
      <Stack.Screen options={{ title: 'Ajanda' }} />
      
      {loading && !refreshing ? (
        <View style={styles.center}><ActivityIndicator size="large" color={PROJECT_COLORS.primary} /></View>
      ) : (
        <SectionList
          sections={sections}
          keyExtractor={(item) => item.etkinlik_id.toString()}
          stickySectionHeadersEnabled={true}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={PROJECT_COLORS.primary} />}
          renderSectionHeader={({ section: { title } }) => (
            <View style={[styles.sectionHeader, { backgroundColor: colorScheme === 'light' ? PROJECT_COLORS.bgSoft : theme.card }]}>
              <Text style={[styles.sectionHeaderText, { color: PROJECT_COLORS.primary }]}>{title}</Text>
            </View>
          )}
          renderItem={({ item }) => {
            const date = new Date(item.baslangic_tarihi);
            const isSubeZiyareti = item.type === 'ziyaret' || item.baslik.toLowerCase().includes('şube') || item.baslik.toLowerCase().includes('ziyaret');
            
            return (
              <Pressable 
                onPress={() => item.type === 'ziyaret' && item.sube_adresi ? openMap(item.sube_adresi) : null}
                style={({ pressed }) => [
                  styles.card, 
                  { backgroundColor: theme.card, borderColor: theme.border, opacity: pressed ? 0.7 : 1 }
                ]}
              >
                <View style={[styles.dateCol, { borderRightColor: theme.border }]}>
                  <Text style={[styles.dateDay, { color: theme.text }]}>{date.getDate()}</Text>
                  <Text style={styles.dateWeekday}>{date.toLocaleDateString('tr-TR', { weekday: 'short' }).toUpperCase()}</Text>
                </View>
                
                <View style={styles.contentCol}>
                  <View style={styles.titleRow}>
                    <Text style={[styles.title, { color: theme.text }]} numberOfLines={2}>{item.baslik}</Text>
                  </View>
                  
                  <View style={styles.detailsRow}>
                    {item.konum && (
                      <View style={styles.detailItem}>
                        <FontAwesome6 name="location-dot" size={10} color="#64748b" />
                        <Text style={styles.detailText} numberOfLines={1}>{item.konum}</Text>
                      </View>
                    )}
                  </View>

                  <View style={styles.badgeRow}>
                    <View style={[styles.bykBadge, { backgroundColor: (isSubeZiyareti ? '#ef4444' : (item.byk_renk || PROJECT_COLORS.primary)) + '20' }]}>
                        <Text style={[styles.bykText, { color: isSubeZiyareti ? '#ef4444' : (item.byk_renk || PROJECT_COLORS.primary) }]}>
                            {item.byk_adi}
                        </Text>
                    </View>
                    {isSubeZiyareti && (
                        <View style={[styles.bykBadge, { backgroundColor: '#3b82f620', marginLeft: 8 }]}>
                            <Text style={[styles.bykText, { color: '#3b82f6' }]}>
                              {item.type === 'ziyaret' ? 'NAVİGASYONU AÇ' : 'KRİTİK GÖREV'}
                            </Text>
                        </View>
                    )}
                  </View>
                </View>
              </Pressable>
            );
          }}
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <FontAwesome6 name="calendar-days" size={60} color={theme.tabIconDefault} style={{opacity: 0.3}} />
              <Text style={styles.emptyText}>Henüz bir etkinlik veya toplantı planlanmamış.</Text>
            </View>
          }
          contentContainerStyle={styles.listContent}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  listContent: { paddingBottom: 100 },
  sectionHeader: { paddingHorizontal: 20, paddingVertical: 12 },
  sectionHeaderText: { fontSize: 13, fontWeight: '800', letterSpacing: 1.2 },
  card: { 
    flexDirection: 'row', 
    marginHorizontal: 16,
    padding: 16, 
    borderRadius: 24, 
    marginBottom: 12, 
    borderWidth: 1,
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 8,
  },
  dateCol: { width: 50, alignItems: 'center', justifyContent: 'center', borderRightWidth: 1, marginRight: 15 },
  dateDay: { fontSize: 22, fontWeight: '900', marginBottom: 2 },
  dateWeekday: { fontSize: 10, color: '#94a3b8', fontWeight: '700' },
  contentCol: { flex: 1 },
  titleRow: { marginBottom: 4 },
  title: { fontSize: 15, fontWeight: '700', lineHeight: 20 },
  detailsRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 10 },
  detailItem: { flexDirection: 'row', alignItems: 'center' },
  detailText: { fontSize: 11, color: '#64748b', marginLeft: 4, fontWeight: '500' },
  badgeRow: { flexDirection: 'row' },
  bykBadge: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 10 },
  bykText: { fontSize: 9, fontWeight: '800', textTransform: 'uppercase' },
  emptyContainer: { alignItems: 'center', justifyContent: 'center', marginTop: 120 },
  emptyText: { marginTop: 20, fontSize: 15, color: '#94a3b8', fontWeight: '600' }
});

