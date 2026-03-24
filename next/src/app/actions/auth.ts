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
