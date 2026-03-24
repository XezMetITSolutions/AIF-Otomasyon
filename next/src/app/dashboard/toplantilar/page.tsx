"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Users, Calendar as CalendarIcon, MapPin, Building, Clock, UsersRound, XCircle, Search } from "lucide-react";
import { getToplantilarAction } from "../../actions/auth";

export default function ToplantilarPage() {
  const [toplantilar, setToplantilar] = useState<any[]>([]);
  const [bykList, setBykList] = useState<any[]>([]);
  const [isAdmin, setIsAdmin] = useState(false);
  const [canManage, setCanManage] = useState(false);
  const [loading, setLoading] = useState(true);

  // Filters
  const [tab, setTab] = useState<"gelecek" | "gecmis">("gelecek");
  const [monthFilter, setMonthFilter] = useState("");
  const [bykFilter, setBykFilter] = useState("");

  useEffect(() => {
    loadToplantilar();
  }, [tab, monthFilter, bykFilter]);

  async function loadToplantilar() {
    setLoading(true);
    const res = await getToplantilarAction({ tab, ay: monthFilter, byk: bykFilter });
    if (res.success) {
      setToplantilar(res.toplantilar || []);
      setBykList(res.bykList || []);
      setIsAdmin(res.isAdmin || false);
      setCanManage(res.canManage || false);
    }
    setLoading(false);
  }

  // Generate Month list (-12 months to +6 months)
  const getMonths = () => {
    const list = [];
    const date = new Date();
    date.setMonth(date.getMonth() - 12);
    for (let i = -12; i <= 6; i++) {
      list.push({
        value: `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}`,
        label: date.toLocaleString('tr-TR', { month: 'long', year: 'numeric' })
      });
      date.setMonth(date.getMonth() + 1);
    }
    return list;
  };
  const monthOptions = getMonths();

  const formatDate = (dateString: string) => {
    const d = new Date(dateString);
    return {
      day: d.getDate(),
      month: d.toLocaleString('tr-TR', { month: 'short' }).toUpperCase(),
      time: d.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' }),
      isPast: d < new Date(),
    };
  };

  return (
    <div className="space-y-6">
      {/* HEADER SECTION */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            <UsersRound className="w-6 h-6 text-emerald-400" /> Toplantı Yönetimi
          </h1>
          <p className="text-zinc-500 text-sm mt-1">Bölge Yürütme Kurulu toplantılarını buradan takip edin.</p>
        </div>
      </div>

      {/* FILTER BAR (GLASS PANEL) */}
      <div className="bg-zinc-900/80 backdrop-blur-md border border-white/5 rounded-2xl p-4 flex flex-col md:flex-row items-center justify-between gap-4 sticky top-20 z-10 shadow-lg shadow-black/20">
        
        {/* TABS (Gelecek - Geçmiş) */}
        <div className="flex bg-zinc-950 rounded-xl p-1 border border-white/5 w-full md:w-auto">
          <button 
            onClick={() => setTab("gelecek")}
            className={`flex-1 md:flex-none px-6 py-2 rounded-lg text-sm font-medium transition-all ${
              tab === "gelecek" ? "bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-md" : "text-zinc-400 hover:text-zinc-200 hover:bg-white/5"
            }`}
          >
            Gelecek
          </button>
          <button 
            onClick={() => setTab("gecmis")}
            className={`flex-1 md:flex-none px-6 py-2 rounded-lg text-sm font-medium transition-all ${
              tab === "gecmis" ? "bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-md" : "text-zinc-400 hover:text-zinc-200 hover:bg-white/5"
            }`}
          >
            Geçmiş
          </button>
        </div>

        {/* SELECT FILTERS */}
        <div className="flex items-center gap-2 w-full md:w-auto">
          <div className="relative">
            <select 
              value={monthFilter}
              onChange={(e) => setMonthFilter(e.target.value)}
              className="appearance-none bg-zinc-950 border border-white/10 rounded-xl pl-4 pr-10 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-emerald-500/50 transition-all cursor-pointer min-w-[140px]"
            >
              <option value="">Tüm Aylar</option>
              {monthOptions.map(m => (
                <option key={m.value} value={m.value}>{m.label}</option>
              ))}
            </select>
          </div>

          {isAdmin && (
            <div className="relative">
              <select 
                value={bykFilter}
                onChange={(e) => setBykFilter(e.target.value)}
                className="appearance-none bg-zinc-950 border border-white/10 rounded-xl pl-4 pr-10 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-emerald-500/50 transition-all cursor-pointer min-w-[160px]"
              >
                <option value="">Tüm Birimler</option>
                {bykList.map(b => (
                  <option key={b.byk_id} value={b.byk_id}>{b.byk_adi}</option>
                ))}
              </select>
            </div>
          )}
        </div>
      </div>

      {/* MEETINGS GRID */}
      {loading ? (
        <div className="flex justify-center items-center py-20 text-emerald-500 font-bold">Yükleniyor...</div>
      ) : toplantilar.length === 0 ? (
        <div className="bg-zinc-900 border border-white/5 rounded-2xl p-12 text-center flex flex-col items-center justify-center space-y-3">
          <div className="w-16 h-16 bg-zinc-800 rounded-full flex items-center justify-center opacity-50 mb-2">
            <Search className="w-8 h-8 text-zinc-500" />
          </div>
          <h3 className="text-zinc-300 font-semibold">Toplantı Bulunamadı</h3>
          <p className="text-zinc-500 text-sm max-w-sm">
            Seçili filtrelere uygun toplantı kaydı bulunmuyor. Farklı aylar veya filtreler deneyebilirsiniz.
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
          {toplantilar.map((toplanti, idx) => {
            const dParts = formatDate(toplanti.toplanti_tarihi);
            const total = Number(toplanti.total_participants) || 0;
            const confirmed = Number(toplanti.confirmed_participants) || 0;
            const percent = total > 0 ? Math.round((confirmed / total) * 100) : 0;
            const isCancelled = toplanti.durum === 'iptal';
            
            return (
              <motion.div 
                initial={{ opacity: 0, y: 15 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: idx * 0.05 }}
                key={toplanti.toplanti_id}
                className={`bg-zinc-900 rounded-2xl border transition-all cursor-pointer group flex flex-col justify-between ${
                  isCancelled 
                    ? "border-red-500/20 shadow-[0_0_15px_-3px_rgba(239,68,68,0.1)] opacity-70" 
                    : dParts.isPast 
                      ? "border-white/5 opacity-80" 
                      : "border-emerald-500/20 hover:border-emerald-500/40 hover:bg-zinc-800/80 hover:-translate-y-1 shadow-[0_4px_20px_-5px_rgba(16,185,129,0.1)]"
                }`}
              >
                <div className="p-5 flex-1 relative overflow-hidden">
                  {/* Cancelled state UI */}
                  {isCancelled && (
                    <div className="absolute top-4 right-4 bg-red-500/10 text-red-500 text-[10px] font-bold px-2 py-1 rounded-md border border-red-500/20 flex items-center gap-1">
                      <XCircle className="w-3 h-3" /> İPTAL EDİLDİ
                    </div>
                  )}

                  <div className="flex gap-4 mb-4">
                    {/* Date Block */}
                    <div className="bg-zinc-950 border border-white/5 rounded-xl min-w-[65px] h-[65px] flex flex-col items-center justify-center p-2 shadow-inner">
                      <span className="text-xl font-bold text-zinc-100 leading-none">{dParts.day}</span>
                      <span className="text-[10px] text-emerald-500 font-bold mt-1 tracking-widest">{dParts.month}</span>
                    </div>

                    <div className="flex-1 pr-4">
                      <h3 className="font-bold text-white text-base leading-tight pt-1 mb-2 line-clamp-2 pr-16">
                        {toplanti.baslik}
                      </h3>
                      <div className="flex flex-col gap-1.5">
                        <span className="text-xs text-zinc-400 flex items-center gap-2 font-medium">
                          <Clock className="w-3.5 h-3.5 text-zinc-500" /> {dParts.time}
                        </span>
                        {toplanti.konum && (
                          <span className="text-xs text-zinc-400 flex items-center gap-2">
                            <MapPin className="w-3.5 h-3.5 text-zinc-500" /> <span className="truncate max-w-[150px]">{toplanti.konum}</span>
                          </span>
                        )}
                        {isAdmin && (
                          <span className="text-xs text-blue-400 flex items-center gap-2 mt-1 bg-blue-500/10 w-fit px-2 py-0.5 rounded-md border border-blue-500/20">
                            <Building className="w-3 h-3" /> {toplanti.byk_adi}
                          </span>
                        )}
                      </div>
                    </div>
                  </div>

                  {toplanti.aciklama && (
                    <p className="text-zinc-500 text-xs leading-relaxed line-clamp-2 mt-2 mb-4">
                      {toplanti.aciklama.replace(/<\/?[^>]+(>|$)/g, "")}
                    </p>
                  )}
                </div>

                {/* Progress Footer */}
                <div className="p-4 bg-zinc-950/50 border-t border-white/5 rounded-b-2xl">
                  <div className="flex justify-between items-center mb-1.5">
                    <span className="text-xs font-semibold text-zinc-400 flex items-center gap-1.5">
                      <Users className="w-3.5 h-3.5" /> Katılım Durumu
                    </span>
                    <span className="text-[10px] font-bold text-zinc-500 bg-zinc-800 px-2 py-0.5 rounded-md">
                      {confirmed} / {total}
                    </span>
                  </div>
                  <div className="w-full bg-zinc-800/80 rounded-full h-1.5 overflow-hidden border border-white/5">
                    <motion.div 
                      initial={{ width: 0 }}
                      animate={{ width: `${percent}%` }}
                      transition={{ duration: 1, delay: 0.2 }}
                      className={`h-full rounded-full ${isCancelled ? 'bg-zinc-600' : 'bg-gradient-to-r from-emerald-500 to-teal-400 shadow-[0_0_10px_0_rgba(16,185,129,0.5)]'}`}
                    ></motion.div>
                  </div>
                </div>
              </motion.div>
            );
          })}
        </div>
      )}
    </div>
  );
}
