const API_BASE_URL = 'https://aifnet.islamfederasyonu.at/api';

export async function fetchStats(userId?: number) {
  try {
    let url = `${API_BASE_URL}/stats.php`;
    if (userId) url += `?userId=${userId}`;
    const response = await fetch(url);
    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Fetch Stats Error:', error);
    return { success: false, message: 'Sunucuya bağlanılamadı.' };
  }
}

export async function fetchUsers() {
  try {
    const response = await fetch(`${API_BASE_URL}/users.php`);
    return await response.json();
  } catch (error) {
    return { success: false, message: 'Sunucuya ulaşılamadı.' };
  }
}

export async function fetchMeetings() {
  try {
    const response = await fetch(`${API_BASE_URL}/meetings.php`);
    return await response.json();
  } catch (error) {
    return { success: false, message: 'Sunucuya ulaşılamadı.' };
  }
}

export async function fetchMeetingDetail(id: string | number) {
  try {
    const response = await fetch(`${API_BASE_URL}/meeting-detail.php?id=${id}`);
    return await response.json();
  } catch (error) {
    return { success: false, message: 'Sunucuya ulaşılamadı.' };
  }
}

export async function fetchTasks(type: string, userId?: number, scope?: string) {
  try {
    let url = `${API_BASE_URL}/tasks.php?type=${type}`;
    if (userId) url += `&userId=${userId}`;
    if (scope) url += `&scope=${scope}`;
    const response = await fetch(url);
    return await response.json();
  } catch (error) {
    return { success: false, message: 'Sunucuya ulaşılamadı.' };
  }
}

export async function fetchBYK() {
  try {
    const response = await fetch(`${API_BASE_URL}/byk.php`);
    return await response.json();
  } catch (error) {
    return { success: false, message: 'Sunucuya ulaşılamadı.' };
  }
}

export async function fetchSubeler() {
  try {
    const response = await fetch(`${API_BASE_URL}/subeler.php`);
    return await response.json();
  } catch (error) {
    return { success: false, message: 'Sunucuya ulaşılamadı.' };
  }
}

export async function fetchEtkinlikler() {
  try {
    const response = await fetch(`${API_BASE_URL}/etkinlikler.php`);
    return await response.json();
  } catch (error) {
    return { success: false, message: 'Sunucuya ulaşılamadı.' };
  }
}

export async function fetchZiyaretler(userId?: number) {
  try {
    let url = `${API_BASE_URL}/ziyaretler.php`;
    if (userId) url += `?userId=${userId}`;
    const response = await fetch(url);
    return await response.json();
  } catch (error) {
    return { success: false, message: 'Sunucuya ulaşılamadı.' };
  }
}

export async function fetchProjeler(userId?: number, scope?: string) {
  try {
    let url = `${API_BASE_URL}/projeler.php`;
    if (userId || scope) url += '?';
    if (userId) url += `userId=${userId}`;
    if (scope) url += `${userId ? '&' : ''}scope=${scope}`;
    const response = await fetch(url);
    return await response.json();
  } catch (error) {
    return { success: false, message: 'Sunucuya ulaşılamadı.' };
  }
}

export async function login(email: string, password: string) {
  try {
    const response = await fetch(`${API_BASE_URL}/login.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ email, password }),
    });
    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Login Error:', error);
    return { success: false, message: 'Giriş başarısız.' };
  }
}

export async function updateProfile(id: number, name: string, email: string) {
  try {
    const response = await fetch(`${API_BASE_URL}/profile-update.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ id, name, email }),
    });
    return await response.json();
  } catch (error) {
    return { success: false, message: 'Sunucuya ulaşılamadı.' };
  }
}

export async function changePassword(id: number, oldPassword: string, newPassword: string) {
  try {
    const response = await fetch(`${API_BASE_URL}/change-password.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ id, old_password: oldPassword, new_password: newPassword }),
    });
    return await response.json();
  } catch (error) {
    return { success: false, message: 'Sunucuya ulaşılamadı.' };
  }
}

export async function downloadMeetingReport(id: string | number) {
  try {
    const response = await fetch(`${API_BASE_URL}/mobile-pdf.php?id=${id}`);
    if (!response.ok) {
        console.error('Download API HTTP Error:', response.status, response.statusText);
        return { success: false, message: `Sunucu hatası: ${response.status}` };
    }
    return await response.json();
  } catch (error) {
    console.error('Download API Fetch Error:', error);
    return { success: false, message: 'Sunucuya ulaşılamadı.' };
  }
}

export async function submitIadeTalebi(userId: number, items: any[], iban: string, total: number) {
  try {
    const response = await fetch(`${API_BASE_URL}/save-iade.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ userId, items, iban, total }),
    });
    return await response.json();
  } catch (error) {
    console.error('Submit Iade Error:', error);
    return { success: false, message: 'Sunucuya ulaşılamadı.' };
  }
}
