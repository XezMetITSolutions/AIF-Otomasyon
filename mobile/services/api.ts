const API_BASE_URL = 'https://aifnet.islamfederasyonu.at/api';

export async function fetchStats() {
  try {
    const response = await fetch(`${API_BASE_URL}/stats.php`);
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

export async function fetchTasks(type: string) {
  try {
    const response = await fetch(`${API_BASE_URL}/tasks.php?type=${type}`);
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

export async function fetchProjeler() {
  try {
    const response = await fetch(`${API_BASE_URL}/projeler.php`);
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

