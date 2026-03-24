"use server";

import { cookies } from "next/headers";

/**
 * Giriş Aksiyonu - PHP Backend'e tünel isteği yapar ve çerezleri taşır.
 */
export async function loginAction(data: { email: string; password: string; remember?: boolean }) {
  try {
    const backendUrl = "https://aifnet.islamfederasyonu.at/api/login.php";

    const res = await fetch(backendUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(data),
    });

    const result = await res.json();

    if (!res.ok || !result.success) {
      throw new Error(result.error || "Giriş başarısız oldu.");
    }

    // Çerezleri (Cookies) Tünelleme / Forwarding
    const setCookieHeader = res.headers.get("Set-Cookie");
    if (setCookieHeader) {
      // PHPSESSID yakala
      const match = setCookieHeader.match(/PHPSESSID=([^;]+)/);
      if (match && match[1]) {
        const cookieStore = await cookies();
        cookieStore.set("PHPSESSID", match[1], {
          httpOnly: true,
          secure: true, // Vercel her zaman Https'dir
          sameSite: "lax",
          path: "/",
        });
      }
    }

    return { success: true, user: result.user };
  } catch (err: any) {
    console.error("Giriş Aksiyon Hatası:", err);
    return { success: false, error: err.message || "Bağlantı hatası oluştu." };
  }
}

/**
 * Çıkış Aksiyonu
 */
export async function logoutAction() {
  const cookieStore = await cookies();
  cookieStore.delete("PHPSESSID");
  return { success: true };
}


/**
 * Aktif Profil Bilgilerini Getir - Çerez (PHPSESSID) ile tünel isteği yapar.
 */
export async function getProfileAction() {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;

    if (!sessionId) {
      return { success: false, error: "Oturum bulunamadı." };
    }

    const res = await fetch("https://aifnet.islamfederasyonu.at/api/profile.php", {
      headers: {
        "Cookie": `PHPSESSID=${sessionId}`,
      },
    });

    const result = await res.json();
    if (!res.ok || !result.success) {
      return { success: false, error: result.error || "Yetkilendirme hatası." };
    }

    return { success: true, user: result.user };
  } catch (err: any) {
    return { success: false, error: err.message || "Profil bağlantı hatası." };
  }
}


/**
 * Dashboard Özet Verilerini Getir - Çerez (PHPSESSID) ile tünel isteği yapar.
 */
export async function getDashboardStats() {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;

    if (!sessionId) {
      return { success: false, error: "Oturum bulunamadı." };
    }

    const res = await fetch("https://aifnet.islamfederasyonu.at/api/dashboard.php", {
      headers: {
        "Cookie": `PHPSESSID=${sessionId}`,
      },
    });

    const result = await res.json();
    if (!res.ok || !result.success) {
      return { success: false, error: result.error || "Veri yüklenemedi." };
    }

    return { 
      success: true, 
      stats: result.stats, 
      meetings: result.meetings, 
      announcements: result.announcements 
    };
  } catch (err: any) {
    return { success: false, error: err.message || "Dashboard bağlantı hatası." };
  }
}



/**
 * Duyuruları Getir
 */
export async function getDuyurularAction() {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const res = await fetch("https://aifnet.islamfederasyonu.at/api/duyurular.php", {
      headers: { "Cookie": `PHPSESSID=${sessionId}` },
      cache: "no-store",
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}

/**
 * Duyuru Ekle
 */
export async function createDuyuruAction(data: { action: string; baslik: string; icerik: string }) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const res = await fetch("https://aifnet.islamfederasyonu.at/api/duyurular.php", {
      method: "POST",
      headers: { 
        "Content-Type": "application/json",
        "Cookie": `PHPSESSID=${sessionId}`
      },
      body: JSON.stringify(data),
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}

/**
 * Duyuru Durumunu Değiştir
 */
export async function toggleDuyuruAction(data: { action: string; duyuru_id: number }) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const res = await fetch("https://aifnet.islamfederasyonu.at/api/duyurular.php", {
      method: "POST",
      headers: { 
        "Content-Type": "application/json",
        "Cookie": `PHPSESSID=${sessionId}`
      },
      body: JSON.stringify(data),
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}



/**
 * Toplantıları Getir
 */
export async function getToplantilarAction(params: { tab?: string; ay?: string; byk?: string }) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const queryParams = new URLSearchParams();
    if (params.tab) queryParams.append("tab", params.tab);
    if (params.ay) queryParams.append("ay", params.ay);
    if (params.byk) queryParams.append("byk", params.byk);

    const res = await fetch(`https://aifnet.islamfederasyonu.at/api/toplantilar.php?${queryParams.toString()}`, {
      headers: { "Cookie": `PHPSESSID=${sessionId}` },
      cache: "no-store",
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}



/**
 * Üyeleri Getir
 */
export async function getUyelerAction(params: { q?: string }) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const queryParams = new URLSearchParams();
    if (params.q) queryParams.append("q", params.q);

    const res = await fetch(`https://aifnet.islamfederasyonu.at/api/uyeler.php?${queryParams.toString()}`, {
      headers: { "Cookie": `PHPSESSID=${sessionId}` },
      cache: "no-store",
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}



/**
 * Çalışma Takvimi - Etkinlikleri Getir
 */
export async function getEtkinliklerAction(params: { ay?: string; yil?: string; birim?: string }) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const queryParams = new URLSearchParams();
    if (params.ay) queryParams.append("ay", params.ay);
    if (params.yil) queryParams.append("yil", params.yil);
    if (params.birim) queryParams.append("birim", params.birim);

    const res = await fetch(`https://aifnet.islamfederasyonu.at/api/etkinlikler.php?${queryParams.toString()}`, {
      headers: { "Cookie": `PHPSESSID=${sessionId}` },
      cache: "no-store",
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}



/**
 * İzin Taleplerini Getir
 */
export async function getIzinTalepleriAction(params: { tab: string; durum?: string }) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const queryParams = new URLSearchParams();
    queryParams.append("tab", params.tab);
    if (params.durum) queryParams.append("durum", params.durum);

    const res = await fetch(`https://aifnet.islamfederasyonu.at/api/izin-talepleri.php?${queryParams.toString()}`, {
      headers: { "Cookie": `PHPSESSID=${sessionId}` },
      cache: "no-store",
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}

/**
 * İzin İşlemleri (Oluşturma, Onaylama, Reddetme, Silme)
 */
export async function actionIzinTalebi(data: any) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const res = await fetch("https://aifnet.islamfederasyonu.at/api/izin-talepleri.php", {
      method: "POST",
      headers: { 
        "Content-Type": "application/json",
        "Cookie": `PHPSESSID=${sessionId}`
      },
      body: JSON.stringify(data),
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}



/**
 * Harcama Taleplerini Getir
 */
export async function getHarcamaTalepleriAction(params: { tab: string; durum?: string }) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const queryParams = new URLSearchParams();
    queryParams.append("tab", params.tab);
    if (params.durum) queryParams.append("durum", params.durum);

    const res = await fetch(`https://aifnet.islamfederasyonu.at/api/harcama-talepleri.php?${queryParams.toString()}`, {
      headers: { "Cookie": `PHPSESSID=${sessionId}` },
      cache: "no-store",
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}

/**
 * Harcama İşlemleri (Oluşturma, Onaylama, Reddetme, Silme)
 */
export async function actionHarcamaTalebi(data: any) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const res = await fetch("https://aifnet.islamfederasyonu.at/api/harcama-talepleri.php", {
      method: "POST",
      headers: { 
        "Content-Type": "application/json",
        "Cookie": `PHPSESSID=${sessionId}`
      },
      body: JSON.stringify(data),
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}



/**
 * Yönetim - Kullanıcıları Getir
 */
export async function getAdminUsersAction(params: { page?: number; search?: string; rol?: string; byk?: string; status?: string }) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const queryParams = new URLSearchParams();
    if (params.page) queryParams.append("page", params.page.toString());
    if (params.search) queryParams.append("search", params.search);
    if (params.rol) queryParams.append("rol", params.rol);
    if (params.byk) queryParams.append("byk", params.byk);
    if (params.status) queryParams.append("status", params.status);

    const res = await fetch(`https://aifnet.islamfederasyonu.at/api/admin-kullanicilar.php?action=list&${queryParams.toString()}`, {
      headers: { "Cookie": `PHPSESSID=${sessionId}` },
      cache: "no-store",
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}

/**
 * Yönetim - Kullanıcı Silme
 */
export async function deleteAdminUserAction(userId: number) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const res = await fetch("https://aifnet.islamfederasyonu.at/api/admin-kullanicilar.php", {
      method: "POST",
      headers: { 
        "Content-Type": "application/json",
        "Cookie": `PHPSESSID=${sessionId}`
      },
      body: JSON.stringify({ action: "delete", kullanici_id: userId }),
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}



/**
 * Yönetim - BYK'ları Getir
 */
export async function getAdminByksAction() {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const res = await fetch(`https://aifnet.islamfederasyonu.at/api/admin-byk.php`, {
      headers: { "Cookie": `PHPSESSID=${sessionId}` },
      cache: "no-store",
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}

/**
 * Yönetim - BYK Silme
 */
export async function deleteAdminBykAction(bykId: number) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const res = await fetch("https://aifnet.islamfederasyonu.at/api/admin-byk.php", {
      method: "POST",
      headers: { 
        "Content-Type": "application/json",
        "Cookie": `PHPSESSID=${sessionId}`
      },
      body: JSON.stringify({ action: "delete", byk_id: bykId }),
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}



/**
 * Yönetim - Alt Birimleri Getir
 */
export async function getAdminAltBirimlerAction(params: { search?: string; byk?: string }) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const queryParams = new URLSearchParams();
    if (params.search) queryParams.append("search", params.search);
    if (params.byk) queryParams.append("byk", params.byk);

    const res = await fetch(`https://aifnet.islamfederasyonu.at/api/admin-alt-birimler.php?${queryParams.toString()}`, {
      headers: { "Cookie": `PHPSESSID=${sessionId}` },
      cache: "no-store",
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}

/**
 * Yönetim - Alt Birim Silme
 */
export async function deleteAdminAltBirimAction(altBirimId: number) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const res = await fetch("https://aifnet.islamfederasyonu.at/api/admin-alt-birimler.php", {
      method: "POST",
      headers: { 
        "Content-Type": "application/json",
        "Cookie": `PHPSESSID=${sessionId}`
      },
      body: JSON.stringify({ action: "delete", alt_birim_id: altBirimId }),
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}



/**
 * Yönetim - Yetkileri Getir
 */
export async function getAdminYetkilerAction() {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const res = await fetch(`https://aifnet.islamfederasyonu.at/api/admin-yetkiler.php`, {
      headers: { "Cookie": `PHPSESSID=${sessionId}` },
      cache: "no-store",
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}

/**
 * Yönetim - Yetkileri Kaydet
 */
export async function saveAdminYetkilerAction(permissions: any) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const res = await fetch("https://aifnet.islamfederasyonu.at/api/admin-yetkiler.php", {
      method: "POST",
      headers: { 
        "Content-Type": "application/json",
        "Cookie": `PHPSESSID=${sessionId}`
      },
      body: JSON.stringify({ action: "save_permissions", permissions }),
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}



/**
 * Yönetim - Demirbaşları Getir
 */
export async function getDemirbaslarAction() {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const res = await fetch(`https://aifnet.islamfederasyonu.at/api/demirbaslar.php`, {
      headers: { "Cookie": `PHPSESSID=${sessionId}` },
      cache: "no-store",
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}

/**
 * İade Formlarını Getir
 */
export async function getIadeFormlariAction(params: { tab: string }) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const queryParams = new URLSearchParams();
    queryParams.append("tab", params.tab);

    const res = await fetch(`https://aifnet.islamfederasyonu.at/api/iade-formlari.php?${queryParams.toString()}`, {
      headers: { "Cookie": `PHPSESSID=${sessionId}` },
      cache: "no-store",
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}

/**
 * Demirbaş Taleplerini Getir
 */
export async function getDemirbasTalepleriAction(params: { tab: string }) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const queryParams = new URLSearchParams();
    queryParams.append("tab", params.tab);

    const res = await fetch(`https://aifnet.islamfederasyonu.at/api/demirbas-talepleri.php?${queryParams.toString()}`, {
      headers: { "Cookie": `PHPSESSID=${sessionId}` },
      cache: "no-store",
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}

/**
 * Demirbaş İşlemleri (Oluşturma)
 */
export async function actionDemirbasTalebi(data: any) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get("PHPSESSID")?.value;
    if (!sessionId) return { success: false, error: "Oturum bulunamadı." };

    const res = await fetch("https://aifnet.islamfederasyonu.at/api/demirbas-talepleri.php", {
      method: "POST",
      headers: { 
        "Content-Type": "application/json",
        "Cookie": `PHPSESSID=${sessionId}`
      },
      body: JSON.stringify(data),
    });

    return await res.json();
  } catch (err: any) {
    return { success: false, error: err.message };
  }
}
