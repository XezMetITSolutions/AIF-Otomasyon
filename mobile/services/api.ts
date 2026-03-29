const API_BASE_URL = 'http://10.113.187.154/api';

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

