"use client";

import { useState } from "react";
import { Settings, Lock, Bell, User, ShieldCheck, Database } from "lucide-react";

export default function AyarlarPage() {
  const [activeTab, setActiveTab] = useState("profil");

  const tabs = [
    { id: "profil", label: "Profil Bilgileri", icon: <User className="w-4 h-4" /> },
    { id: "guvenlik", label: "Güvenlik", icon: <Lock className="w-4 h-4" /> },
    { id: "bildirimler", label: "Bildirimler", icon: <Bell className="w-4 h-4" /> },
    { id: "sistem", label: "Sistem Ayarları", icon: <Database className="w-4 h-4" /> },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col">
        <h1 className="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
          <Settings className="w-6 h-6 text-emerald-400" /> Hesap ve Sistem Ayarları
        </h1>
        <p className="text-zinc-500 text-sm mt-1">Oturum bilgilerinizle beraber genel sistem parametrelerini yapılandırın.</p>
      </div>

      <div className="flex flex-col md:flex-row gap-6">
         {/* Sidebar Navigation */}
         <div className="w-full md:w-64 space-y-1">
            {tabs.map(t => (
               <button 
                 key={t.id} 
                 onClick={() => setActiveTab(t.id)}
                 className={`flex items-center gap-2.5 px-4 py-3 rounded-xl w-full text-left text-sm font-medium transition-all ${
                    activeTab === t.id ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-md' : 'text-zinc-400 hover:text-zinc-200 hover:bg-white/5'
                 }`}
               >
                  {t.icon}
                  {t.label}
               </button>
            ))}
         </div>

         {/* Content Block */}
         <div className="flex-1 bg-zinc-900 border border-white/5 rounded-2xl p-6 shadow-xl shadow-black/20">
            {activeTab === 'profil' && (
               <div className="space-y-4">
                  <h3 className="text-lg font-bold text-white mb-2 pb-2 border-b border-white/5">Görüntüleme Bilgileri</h3>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                     <div>
                        <label className="text-xs text-zinc-500 mb-1 block">Ad Soyad</label>
                        <input type="text" value="Yasin Çakmak" disabled className="w-full bg-zinc-950/50 border border-white/10 rounded-xl px-3 py-2 text-sm text-zinc-400 cursor-not-allowed" />
                     </div>
                     <div>
                        <label className="text-xs text-zinc-500 mb-1 block">E-Posta</label>
                        <input type="text" value="yasin@islamfederasyonu.at" disabled className="w-full bg-zinc-950/50 border border-white/10 rounded-xl px-3 py-2 text-sm text-zinc-400 cursor-not-allowed" />
                     </div>
                  </div>
               </div>
            )}

            {activeTab !== 'profil' && (
               <div className="flex flex-col items-center justify-center py-20 text-center opacity-60">
                   <ShieldCheck className="w-12 h-12 text-zinc-500 mb-2" />
                   <p className="text-sm font-semibold text-zinc-300">Güvenlik ve Yapılandırma</p>
                   <p className="text-xs text-zinc-500 max-w-xs">Şifre sıfırlama parametreleri ve API anahtarları yönetimi bu sekmede kurulacaktır.</p>
               </div>
            )}
         </div>
      </div>
    </div>
  );
}
