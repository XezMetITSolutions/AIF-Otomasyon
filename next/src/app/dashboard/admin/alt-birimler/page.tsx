"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Network, Search, Trash2, Edit, Plus, AlertCircle, CheckCircle2, XCircle, MapPin, UserCheck, X } from "lucide-react";
import { getAdminAltBirimlerAction, deleteAdminAltBirimAction } from "../../../actions/auth";

export default function AdminAltBirimlerPage() {
  const [subUnits, setSubUnits] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  
  // Filters
  const [search, setSearch] = useState("");
  const [debounceSearch, setDebounceSearch] = useState("");
  const [bykFilter, setBykFilter] = useState("");
  const [byks, setByks] = useState<any[]>([]);

  const [message, setMessage] = useState<{ text: string; type: "success" | "error" } | null>(null);

  useEffect(() => {
    const handler = setTimeout(() => {
      setDebounceSearch(search);
    }, 500);
    return () => clearTimeout(handler);
  }, [search]);

  useEffect(() => {
    loadSubUnits();
  }, [debounceSearch, bykFilter]);

  async function loadSubUnits() {
    setLoading(true);
    const res = await getAdminAltBirimlerAction({ search: debounceSearch, byk: bykFilter });

    if (res.success) {
      setSubUnits(res.subUnits);
      if (res.constants) setByks(res.constants.byks || []);
    } else {
      setMessage({ text: res.error || "Alt birimler yüklenemedi.", type: "error" });
    }
    setLoading(false);
  }

  const showMessage = (text: string, type: "success" | "error") => {
    setMessage({ text, type });
    setTimeout(() => setMessage(null), 4000);
  };

  const handleDelete = async (id: number, name: string) => {
    if (!confirm(`"${name}" isimli alt birimi silmek istediğinize emin misiniz?`)) return;

    const res = await deleteAdminAltBirimAction(id);
    if (res.success) {
      showMessage(res.message, "success");
      await loadSubUnits();
    } else {
      showMessage(res.error, "error");
    }
  };

  const formatDate = (dateString?: string) => {
    if (!dateString) return "-";
    return new Date(dateString).toLocaleDateString("tr-TR");
  };

  return (
    <div className="space-y-6">
      {/* BAŞLIK */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            <Network className="w-6 h-6 text-emerald-400" /> Alt Birimler Yönetimi
          </h1>
          <p className="text-zinc-500 text-sm mt-1">Sisteme kayıtlı BYK şubelerinin tüm hiyerarşik alt organizasyonlarını yönetin. (Toplam: {subUnits.length})</p>
        </div>
        <div>
          <button className="bg-emerald-600 hover:bg-emerald-500 text-white font-medium px-4 py-2 rounded-xl transition-all flex items-center gap-2 text-sm shadow-lg shadow-emerald-500/20 w-full justify-center md:w-auto md:justify-start">
            <Plus className="w-4 h-4" /> Yeni Alt Birim Ekle
          </button>
        </div>
      </div>

      <AnimatePresence>
        {message && (
          <motion.div
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -10 }}
            className={`p-4 rounded-xl flex items-center gap-3 border ${
              message.type === "success" ? "bg-emerald-500/10 border-emerald-500/20 text-emerald-400" : "bg-red-500/10 border-red-500/20 text-red-400"
            }`}
          >
            {message.type === "success" ? <CheckCircle2 className="w-5 h-5" /> : <XCircle className="w-5 h-5" />}
            <span className="text-sm font-medium">{message.text}</span>
          </motion.div>
        )}
      </AnimatePresence>

      {/* FİLTRELER */}
      <div className="bg-zinc-900 border border-white/5 rounded-2xl p-4 flex flex-wrap gap-4 items-end shadow-xl shadow-black/20">
        <div className="flex-1 min-w-[200px]">
          <label className="text-xs font-semibold text-zinc-500 mb-1.5 block">Arama</label>
          <div className="relative">
            <Search className="w-4 h-4 absolute left-3 top-3 text-zinc-400" />
            <input 
              type="text" 
              placeholder="Birim adı veya açıklama ara..." 
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full bg-zinc-950 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-emerald-500/50 transition-colors"
            />
          </div>
        </div>

        <div className="w-[200px]">
          <label className="text-xs font-semibold text-zinc-500 mb-1.5 block">BYK (Bölge / Şube)</label>
          <select 
            value={bykFilter}
            onChange={(e) => setBykFilter(e.target.value)}
            className="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-emerald-500/50"
          >
            <option value="">Tüm BYK'lar</option>
            {byks.map(b => (
              <option key={b.id} value={b.id}>{b.name} ({b.code})</option>
            ))}
          </select>
        </div>

        {(search || bykFilter) && (
           <button 
             onClick={() => {
                setSearch(""); setDebounceSearch(""); setBykFilter("");
             }}
             className="bg-zinc-800 hover:bg-zinc-700 text-zinc-400 p-2.5 rounded-xl transition-all h-[42px]"
             title="Filtreleri Temizle"
           >
             <X className="w-5 h-5" />
           </button>
        )}
      </div>

      {/* LİSTE */}
      <div className="bg-zinc-900 border border-white/5 rounded-2xl overflow-hidden min-h-[500px]">
        {loading ? (
          <div className="flex justify-center flex-1 items-center py-20">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-500"></div>
          </div>
        ) : subUnits.length === 0 ? (
           <div className="flex flex-col flex-1 items-center justify-center py-20 opacity-50">
             <AlertCircle className="w-12 h-12 text-zinc-500 mb-3" />
             <p className="text-zinc-400">Bu filtrelere uygun alt birim bulunamadı.</p>
           </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm whitespace-nowrap">
              <thead className="bg-zinc-950/50 text-zinc-400 font-medium">
                <tr>
                  <th className="px-5 py-4 pl-6">Alt Birim / Kod</th>
                  <th className="px-5 py-4">Bağlı BYK</th>
                  <th className="px-5 py-4">Birim Sorumlusu</th>
                  <th className="px-5 py-4">Oluşturma Tarihi</th>
                  <th className="px-5 py-4 text-right pr-6">İşlemler</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-white/5">
                {subUnits.map(b => {
                  const subId = b.id ?? b.alt_birim_id;
                  const name = b.name ?? b.alt_birim_adi;
                  const desc = b.description || '';
                  return (
                    <tr key={subId} className="hover:bg-white/[0.02] transition-colors group">
                      <td className="px-5 py-4 pl-6">
                         <div className="font-bold text-zinc-200">{name}</div>
                         {desc && !b.sorumlu && (
                            <div className="text-[10px] text-zinc-500 truncate max-w-[200px]" title={desc}>{desc}</div>
                         )}
                      </td>
                      <td className="px-5 py-4">
                         <div className="flex items-center gap-2">
                            <span className="w-2 h-2 rounded-full" style={{ backgroundColor: b.byk_renk || '#009872' }}></span>
                            <span className="font-medium text-zinc-300">{b.byk_adi}</span>
                            {b.byk_kodu && <span className="text-[10px] text-zinc-600 bg-zinc-950 px-1 py-0.5 rounded">({b.byk_kodu})</span>}
                         </div>
                      </td>
                      <td className="px-5 py-4">
                         {b.sorumlu ? (
                            <div className="flex items-center gap-2 text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2 py-1 rounded w-fit">
                               <UserCheck className="w-3.5 h-3.5" />
                               <span className="text-xs font-bold">{b.sorumlu}</span>
                            </div>
                         ) : (
                            <span className="text-zinc-600">-</span>
                         )}
                      </td>
                      <td className="px-5 py-4">
                         <div className="text-[11px] font-mono text-zinc-500">{formatDate(b.created_at ?? b.olusturma_tarihi)}</div>
                      </td>
                      <td className="px-5 py-4 text-right pr-6 space-x-1 opacity-100 md:opacity-0 group-hover:opacity-100 transition-opacity">
                         <button className="p-1.5 bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 rounded-lg transition-colors inline-block" title="Düzenle">
                            <Edit className="w-4 h-4" />
                         </button>
                         <button onClick={() => handleDelete(subId, name)} className="p-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-500 rounded-lg transition-colors inline-block" title="Sil">
                            <Trash2 className="w-4 h-4" />
                         </button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
