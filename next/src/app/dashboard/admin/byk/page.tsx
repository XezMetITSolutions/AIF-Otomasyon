"use client";

import { useState, useEffect } from "react";
import { Building, Users, Trash2, Edit, Plus, AlertCircle, MapPin, MoreVertical, XCircle, CheckCircle2, X } from "lucide-react";
import { getAdminByksAction, deleteAdminBykAction, saveAdminBykAction } from "../../../actions/auth";
import { motion, AnimatePresence } from "framer-motion";

export default function AdminBykPage() {
  const [byks, setByks] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState<{ text: string; type: "success" | "error" } | null>(null);

  // Modal State
  const [modalOpen, setModalOpen] = useState(false);
  const [selectedByk, setSelectedByk] = useState<any>(null);
  const [isSubmitLoading, setIsSubmitLoading] = useState(false);
  const [formData, setFormData] = useState({
    name: "",
    code: "",
    color: "#009872",
    description: ""
  });

  useEffect(() => {
    loadByks();
  }, []);

  async function loadByks() {
    setLoading(true);
    const res = await getAdminByksAction();

    if (res.success) {
      setByks(res.byks);
    } else {
      setMessage({ text: res.error || "BYK listesi alınamadı.", type: "error" });
    }
    setLoading(false);
  }

  const showMessage = (text: string, type: "success" | "error") => {
    setMessage({ text, type });
    setTimeout(() => setMessage(null), 4000);
  };

  const openModal = (byk: any = null) => {
    if (byk) {
      setSelectedByk(byk);
      setFormData({
        name: byk.name ?? byk.byk_adi,
        code: byk.code ?? byk.byk_kodu,
        color: byk.color ?? byk.renk_kodu ?? "#009872",
        description: byk.description || ""
      });
    } else {
      setSelectedByk(null);
      setFormData({
        name: "",
        code: "",
        color: "#009872",
        description: ""
      });
    }
    setModalOpen(true);
  };

  const closeModal = () => {
    setModalOpen(false);
    setSelectedByk(null);
  };

  const handleSaveByk = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitLoading(true);

    try {
      const data = {
        action: selectedByk ? "update" : "create",
        byk_id: selectedByk ? (selectedByk.id ?? selectedByk.byk_id) : undefined,
        ...formData
      };

      const res = await saveAdminBykAction(data);
      if (res.success) {
        showMessage(res.message || "BYK kaydedildi.", "success");
        closeModal();
        await loadByks();
      } else {
        showMessage(res.error || "Bir hata oluştu.", "error");
      }
    } catch (err: any) {
      showMessage(err.message || "Bağlantı hatası.", "error");
    } finally {
      setIsSubmitLoading(false);
    }
  };

  const handleDelete = async (id: number, name: string) => {
    if (!confirm(`"${name}" isimli BYK/Bölgeyi silmek istediğinize emin misiniz?`)) return;

    const res = await deleteAdminBykAction(id);
    if (res.success) {
      showMessage(res.message, "success");
      await loadByks();
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
            <Building className="w-6 h-6 text-emerald-400" /> BYK Yönetimi
          </h1>
          <p className="text-zinc-500 text-sm mt-1">Bölgeler, şubeler (BYK / KGT) ve bunlara ait tanımlamaları yönetin.</p>
        </div>
        <div>
          <button onClick={() => openModal()} className="bg-emerald-600 hover:bg-emerald-500 text-white font-medium px-4 py-2 rounded-xl transition-all flex items-center gap-2 text-sm shadow-lg shadow-emerald-500/20 w-full justify-center md:w-auto md:justify-start">
            <Plus className="w-4 h-4" /> Yeni BYK Ekle
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

      {/* BYK MODAL */}
      <AnimatePresence>
        {modalOpen && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <motion.div 
              initial={{ scale: 0.9, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.9, opacity: 0 }}
              className="bg-zinc-900 border border-white/10 rounded-3xl p-6 w-full max-w-lg shadow-2xl relative"
            >
              <button onClick={closeModal} className="absolute top-4 right-4 text-zinc-500 hover:text-white transition-colors">
                <X className="w-6 h-6" />
              </button>
              
              <h2 className="text-xl font-bold text-white mb-6 flex items-center gap-3">
                 {selectedByk ? <Edit className="w-6 h-6 text-sky-400" /> : <Plus className="w-6 h-6 text-emerald-400" />}
                 {selectedByk ? "BYK Düzenle" : "Yeni BYK Ekle"}
              </h2>

              <form onSubmit={handleSaveByk} className="space-y-5">
                <div className="space-y-2">
                  <label className="text-xs font-bold text-zinc-500 uppercase">BYK / Bölge Adı</label>
                  <input 
                    required
                    type="text" 
                    placeholder="Örn: Viyana 10. Bölge"
                    value={formData.name}
                    onChange={(e) => setFormData({...formData, name: e.target.value})}
                    className="w-full bg-zinc-950 border border-white/5 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500/50 transition-colors"
                  />
                </div>
                
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <label className="text-xs font-bold text-zinc-500 uppercase">Birim Kodu</label>
                    <input 
                      required
                      type="text" 
                      placeholder="Örn: AT, GT, KGT"
                      value={formData.code}
                      onChange={(e) => setFormData({...formData, code: e.target.value})}
                      className="w-full bg-zinc-950 border border-white/5 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500/50 transition-colors"
                    />
                  </div>
                  <div className="space-y-2">
                    <label className="text-xs font-bold text-zinc-500 uppercase">Renk Kodu</label>
                    <div className="flex gap-2">
                       <input 
                         type="color" 
                         value={formData.color}
                         onChange={(e) => setFormData({...formData, color: e.target.value})}
                         className="w-12 h-12 bg-zinc-950 border border-white/5 rounded-xl cursor-pointer p-1"
                       />
                       <input 
                         type="text" 
                         value={formData.color}
                         onChange={(e) => setFormData({...formData, color: e.target.value})}
                         className="flex-1 bg-zinc-950 border border-white/5 rounded-xl px-4 text-white focus:outline-none text-sm"
                       />
                    </div>
                  </div>
                </div>

                <div className="space-y-2">
                  <label className="text-xs font-bold text-zinc-500 uppercase">Açıklama (Opsiyonel)</label>
                  <textarea 
                    rows={3}
                    value={formData.description}
                    onChange={(e) => setFormData({...formData, description: e.target.value})}
                    className="w-full bg-zinc-950 border border-white/5 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500/50 transition-colors resize-none text-sm"
                  />
                </div>

                <div className="flex justify-end gap-3 pt-4">
                  <button type="button" onClick={closeModal} className="px-6 py-3 rounded-xl text-zinc-400 hover:bg-white/5 transition-all text-sm font-bold">İptal</button>
                  <button 
                    disabled={isSubmitLoading} 
                    type="submit" 
                    className="bg-emerald-600 hover:bg-emerald-500 text-white px-8 py-3 rounded-xl transition-all text-sm font-bold shadow-lg shadow-emerald-500/20 disabled:opacity-50"
                  >
                    {isSubmitLoading ? "Kaydediliyor..." : selectedByk ? "Güncelle" : "Oluştur"}
                  </button>
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {loading ? (
         <div className="flex justify-center items-center py-20">
           <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-500"></div>
         </div>
      ) : byks.length === 0 ? (
         <div className="flex flex-col flex-1 items-center justify-center py-20 opacity-50 bg-zinc-900 border border-white/5 rounded-2xl">
           <AlertCircle className="w-12 h-12 text-zinc-500 mb-3" />
           <p className="text-zinc-400">Henüz hiçbir BYK veya Bölge eklenmemiş.</p>
         </div>
      ) : (
         <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
           {byks.map(b => {
             const bykName = b.name ?? b.byk_adi;
             const bykCode = b.code ?? b.byk_kodu;
             const bykColor = b.color ?? b.renk_kodu ?? "#009872";
             const created = b.created_at ?? b.olusturma_tarihi;
             const uCount = parseInt(b.kullanici_sayisi || '0');
             const bykId = b.id ?? b.byk_id;

             return (
              <div key={bykId} className="bg-zinc-900/40 relative overflow-hidden border border-white/5 rounded-3xl p-6 hover:bg-zinc-900/80 transition-all group flex flex-col justify-between">
                {/* Sol Banner */}
                <div className="absolute top-0 left-0 w-1.5 h-full opacity-60" style={{ backgroundColor: bykColor }}></div>
                
                <div>
                   <div className="flex justify-between items-start mb-4">
                     <div className="flex flex-col gap-1 items-start">
                        <span className="px-2.5 py-1 rounded-lg text-xs font-bold uppercase tracking-widest text-zinc-200 border border-white/10" style={{ backgroundColor: `${bykColor}20` }}>
                           {bykCode}
                        </span>
                        <h2 className="text-lg font-bold text-white mt-2 leading-tight">{bykName}</h2>
                     </div>
                     <div className="flex relative">
                        <button className="text-zinc-500 hover:text-white p-2" title="Eylemler">
                           <MoreVertical className="w-4 h-4" />
                        </button>
                     </div>
                   </div>

                   {b.description && (
                     <p className="text-sm text-zinc-500 mb-4 line-clamp-2" title={b.description}>{b.description}</p>
                   )}
                </div>

                <div className="mt-4 pt-4 border-t border-white/5 flex items-center justify-between">
                   <div className="flex items-center gap-2 text-zinc-400 text-sm">
                      <Users className="w-4 h-4" />
                      <span><span className="text-white font-bold">{uCount}</span> Üye</span>
                   </div>
                   <div className="text-[10px] text-zinc-600 font-mono">
                      {formatDate(created)}
                   </div>
                </div>

                {/* Hover Actions */}
                <div className="absolute top-0 right-0 w-full h-full bg-zinc-950/90 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-all flex items-center justify-center gap-3">
                   <button onClick={() => openModal(b)} className="bg-zinc-800 hover:bg-zinc-700 text-white p-3 rounded-full transition-transform transform hover:scale-110" title="Düzenle">
                      <Edit className="w-5 h-5" />
                   </button>
                   <button onClick={() => handleDelete(bykId, bykName)} className="bg-red-500/20 hover:bg-red-600 text-red-500 hover:text-white p-3 rounded-full transition-transform transform hover:scale-110" title="Sil">
                      <Trash2 className="w-5 h-5" />
                   </button>
                </div>
              </div>
             );
           })}
         </div>
      )}
    </div>
  );
}
