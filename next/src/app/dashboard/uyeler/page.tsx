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

      {/* MEMBERS LIST (TABLE) */}
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
        <div className="overflow-x-auto">
          <div className="inline-block min-w-full align-middle">
            <div className="overflow-hidden border border-white/5 rounded-2xl bg-zinc-900/50 backdrop-blur-sm">
              <table className="min-w-full divide-y divide-white/5">
                <thead className="bg-zinc-900/80">
                  <tr>
                    <th scope="col" className="px-6 py-4 text-left text-xs font-bold text-zinc-400 uppercase tracking-wider">Üye Bilgileri</th>
                    <th scope="col" className="px-6 py-4 text-left text-xs font-bold text-zinc-400 uppercase tracking-wider">Yetki Düzeyi</th>
                    <th scope="col" className="px-6 py-4 text-left text-xs font-bold text-zinc-400 uppercase tracking-wider">İletişim</th>
                    <th scope="col" className="px-6 py-4 text-left text-xs font-bold text-zinc-400 uppercase tracking-wider">Son Giriş</th>
                    <th scope="col" className="hidden md:table-cell px-6 py-4 text-right text-xs font-bold text-zinc-400 uppercase tracking-wider">Durum</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-white/5 bg-transparent">
                  {uyeler.map((uye, idx) => (
                    <motion.tr 
                      initial={{ opacity: 0, y: 10 }}
                      animate={{ opacity: 1, y: 0 }}
                      transition={{ delay: idx * 0.03 }}
                      key={uye.kullanici_id}
                      className="hover:bg-white/5 transition-colors group"
                    >
                      {/* Member Info */}
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center gap-3">
                          <div className={`w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm shadow-inner shrink-0 ${
                            Number(uye.aktif) === 1 ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' : 'bg-zinc-800 text-zinc-500 border border-white/5'
                          }`}>
                            {uye.ad.charAt(0)}{uye.soyad.charAt(0)}
                          </div>
                          <div>
                            <div className="text-sm font-bold text-zinc-100">{uye.ad} {uye.soyad}</div>
                            <div className="text-xs text-zinc-500 md:hidden flex items-center gap-1 mt-0.5">
                              {Number(uye.aktif) === 1 ? (
                                <span className="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                              ) : (
                                <span className="w-1.5 h-1.5 rounded-full bg-zinc-600"></span>
                              )}
                              {Number(uye.aktif) === 1 ? 'Aktif' : 'Pasif'}
                            </div>
                          </div>
                        </div>
                      </td>

                      {/* Role Info */}
                      <td className="px-6 py-4 whitespace-nowrap text-sm">
                        {uye.rol_adi === 'baskan' || uye.rol_adi === 'admin' ? (
                          <span className="inline-flex items-center gap-1 text-[10px] font-bold tracking-wider text-amber-400 bg-amber-500/10 px-2.5 py-1 rounded-lg border border-amber-500/20">
                            <ShieldCheck className="w-3 h-3" /> YÖNETİCİ
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1 text-[10px] font-bold tracking-wider text-blue-400 bg-blue-500/10 px-2.5 py-1 rounded-lg border border-blue-500/20">
                            <Shield className="w-3 h-3" /> ÜYE
                          </span>
                        )}
                      </td>

                      {/* Contact Info */}
                      <td className="px-6 py-4 whitespace-nowrap">
                        {canManage ? (
                          <div className="space-y-1">
                            <div className="flex items-center gap-2 text-xs text-zinc-300">
                              <Mail className="w-3 h-3 text-zinc-500" /> {uye.email}
                            </div>
                            {uye.telefon && (
                              <div className="flex items-center gap-2 text-xs text-zinc-400">
                                <Phone className="w-3 h-3 text-zinc-600" /> {uye.telefon}
                              </div>
                            )}
                          </div>
                        ) : (
                          <span className="text-[11px] text-zinc-600 flex items-center gap-1.5">
                            <Shield className="w-3 h-3 opacity-50" /> Kısıtlı
                          </span>
                        )}
                      </td>

                      {/* Last Login */}
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center gap-2 text-xs text-zinc-500">
                          <Clock className="w-3 h-3" />
                          {uye.son_giris ? (
                            <span className="text-zinc-400 font-medium">{formatDate(uye.son_giris)}</span>
                          ) : (
                            <span className="text-zinc-600 italic">Hiç girmedi</span>
                          )}
                        </div>
                      </td>

                      {/* Status (Desktop only) */}
                      <td className="hidden md:table-cell px-6 py-4 whitespace-nowrap text-right">
                        {Number(uye.aktif) === 1 ? (
                          <span className="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-emerald-500/10 text-emerald-500 text-[10px] font-bold border border-emerald-500/20">
                            <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            AKTİF
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-zinc-800 text-zinc-500 text-[10px] font-bold border border-white/5">
                            <span className="w-1.5 h-1.5 rounded-full bg-zinc-600"></span>
                            PASİF
                          </span>
                        )}
                      </td>
                    </motion.tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
