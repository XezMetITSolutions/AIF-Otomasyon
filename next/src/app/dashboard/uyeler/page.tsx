"use client";

import { useState, useEffect } from "react";
import { motion } from "framer-motion";
import { Users, Search, Mail, Phone, Clock, UserX, ShieldCheck, Shield } from "lucide-react";
import { getUyelerAction } from "../../actions/auth";

export default function UyelerPage() {
  const [uyeler, setUyeler] = useState<any[]>([]);
  const [stats, setStats] = useState({ total: 0, active: 0 });
  const [canManage, setCanManage] = useState(false);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState("");

  useEffect(() => {
    // Delay load to reduce requests during typing
    const delayDebounceFn = setTimeout(() => {
      loadUyeler(searchQuery);
    }, 400);

    return () => clearTimeout(delayDebounceFn);
  }, [searchQuery]);

  async function loadUyeler(q: string) {
    setLoading(true);
    const res = await getUyelerAction({ q });
    if (res.success) {
      setUyeler(res.uyeler || []);
      setStats(res.stats || { total: 0, active: 0 });
      setCanManage(res.canManage || false);
    }
    setLoading(false);
  }

  const formatDate = (dateString: string | null) => {
    if (!dateString) return null;
    const d = new Date(dateString);
    return `${d.toLocaleDateString("tr-TR")} ${d.toLocaleTimeString("tr-TR", { hour: "2-digit", minute: "2-digit" })}`;
  };

  return (
    <div className="space-y-6">
      {/* HEADER SECTION */}
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            <Users className="w-6 h-6 text-emerald-400" /> Üyeler
          </h1>
          <p className="text-zinc-500 text-sm mt-1">BYK'nıza bağlı aktif tüm üyeleri görüntüleyin.</p>
        </div>
        
        <div className="flex items-center gap-3">
          <div className="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-3 py-1.5 rounded-lg text-xs font-semibold">
            Aktif: {stats.active}
          </div>
          <div className="bg-zinc-800 border border-white/10 text-zinc-300 px-3 py-1.5 rounded-lg text-xs font-semibold">
            Toplam: {stats.total}
          </div>
        </div>
      </div>

      {/* FILTER BAR */}
      <div className="bg-zinc-900 border border-white/5 rounded-2xl p-4 sticky top-20 z-10 shadow-xl shadow-black/20">
        <div className="relative w-full max-w-md">
          <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <Search className="h-5 w-5 text-zinc-500" />
          </div>
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="block w-full pl-10 pr-3 py-2.5 border border-white/10 rounded-xl leading-5 bg-zinc-950 text-zinc-300 placeholder-zinc-500 focus:outline-none focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/50 sm:text-sm transition-all"
            placeholder={`İsim${canManage ? ', e-posta veya telefon' : ''} arayın...`}
          />
          {searchQuery && (
            <button 
              onClick={() => setSearchQuery("")}
              className="absolute inset-y-0 right-0 pr-3 flex items-center text-zinc-500 hover:text-white"
            >
              <UserX className="h-4 w-4" />
            </button>
          )}
        </div>
      </div>

      {/* MEMBERS GRID */}
      {loading && uyeler.length === 0 ? (
        <div className="flex justify-center items-center py-20 text-emerald-500 font-bold">Üyeler Yükleniyor...</div>
      ) : uyeler.length === 0 ? (
        <div className="bg-zinc-900 border border-white/5 rounded-2xl p-12 text-center flex flex-col items-center justify-center space-y-3">
          <div className="w-16 h-16 bg-zinc-800 rounded-full flex items-center justify-center opacity-50 mb-2">
            <UserX className="w-8 h-8 text-zinc-500" />
          </div>
          <h3 className="text-zinc-300 font-semibold">Üye Bulunamadı</h3>
          <p className="text-zinc-500 text-sm max-w-sm">
            Aradığınız kriterlere uygun herhangi bir üye bulunamadı. Aramanızı temizleyip tekrar deneyebilirsiniz.
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
          {uyeler.map((uye, idx) => (
            <motion.div 
              initial={{ opacity: 0, scale: 0.95 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ delay: idx * 0.05 }}
              key={uye.kullanici_id}
              className="bg-zinc-900 relative rounded-2xl border border-white/5 hover:border-white/10 hover:bg-zinc-800/50 transition-all group overflow-hidden"
            >
              {Number(uye.aktif) === 0 && (
                <div className="absolute top-0 right-0 w-16 h-16 overflow-hidden">
                  <div className="absolute transform rotate-45 bg-zinc-700 text-center text-white font-bold text-[8px] tracking-wider py-1 right-[-20px] top-[16px] w-[85px] shadow-md">
                    PASİF
                  </div>
                </div>
              )}

              <div className="p-5">
                {/* Profile Header Block */}
                <div className="flex items-center gap-3 mb-4">
                  <div className={`w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg shadow-inner ${
                    Number(uye.aktif) === 1 ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' : 'bg-zinc-800 text-zinc-500 border border-white/5'
                  }`}>
                    {uye.ad.charAt(0)}{uye.soyad.charAt(0)}
                  </div>
                  <div>
                    <h3 className="font-bold text-zinc-100 leading-tight">
                      {uye.ad} {uye.soyad}
                    </h3>
                    <div className="flex items-center gap-1.5 mt-1">
                      {uye.rol_adi === 'baskan' || uye.rol_adi === 'admin' ? (
                        <span className="text-[10px] font-bold tracking-wider text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-md border border-amber-500/20 flex items-center gap-1">
                          <ShieldCheck className="w-3 h-3" /> YÖNETİCİ
                        </span>
                      ) : (
                        <span className="text-[10px] font-bold tracking-wider text-blue-400 bg-blue-500/10 px-2 py-0.5 rounded-md border border-blue-500/20 flex items-center gap-1">
                          <Shield className="w-3 h-3" /> ÜYE
                        </span>
                      )}
                    </div>
                  </div>
                </div>

                {/* Profile Details */}
                <div className="space-y-2.5 mt-5">
                  {canManage ? (
                    <>
                      <div className="flex items-center gap-3 text-sm">
                        <div className="w-7 h-7 rounded-lg bg-zinc-950 flex items-center justify-center border border-white/5 shrink-0">
                          <Mail className="w-3.5 h-3.5 text-zinc-500" />
                        </div>
                        <span className="text-zinc-300 truncate" title={uye.email}>{uye.email}</span>
                      </div>
                      
                      {uye.telefon && (
                        <div className="flex items-center gap-3 text-sm">
                          <div className="w-7 h-7 rounded-lg bg-zinc-950 flex items-center justify-center border border-white/5 shrink-0">
                            <Phone className="w-3.5 h-3.5 text-zinc-500" />
                          </div>
                          <span className="text-zinc-400">{uye.telefon}</span>
                        </div>
                      )}

                      {uye.son_giris && (
                        <div className="flex items-center gap-3 text-xs mt-2 pt-2 border-t border-white/5">
                          <div className="w-7 h-7 rounded-lg bg-zinc-950 flex items-center justify-center border border-white/5 shrink-0">
                            <Clock className="w-3.5 h-3.5 text-zinc-500" />
                          </div>
                          <span className="text-zinc-500 font-medium">Son Giriş: <span className="text-zinc-400">{formatDate(uye.son_giris)}</span></span>
                        </div>
                      )}
                    </>
                  ) : (
                    <div className="bg-zinc-950 border border-white/5 rounded-xl p-3 text-center text-zinc-600 text-[11px] font-medium flex items-center justify-center gap-2">
                       <Shield className="w-3.5 h-3.5" /> İletişim bilgileri kısıtlıdır
                    </div>
                  )}
                </div>
              </div>
            </motion.div>
          ))}
        </div>
      )}
    </div>
  );
}
