"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Megaphone, PenLine, Send, Clock, User, CheckCircle2, XCircle } from "lucide-react";
import { getDuyurularAction, createDuyuruAction, toggleDuyuruAction } from "../../actions/auth";

export default function DuyurularPage() {
  const [duyurular, setDuyurular] = useState<any[]>([]);
  const [canManage, setCanManage] = useState(false);
  const [loading, setLoading] = useState(true);

  // Form states
  const [baslik, setBaslik] = useState("");
  const [icerik, setIcerik] = useState("");
  const [submitLoading, setSubmitLoading] = useState(false);
  const [message, setMessage] = useState<{ text: string; type: "success" | "error" } | null>(null);

  useEffect(() => {
    loadDuyurular();
  }, []);

  async function loadDuyurular() {
    setLoading(true);
    const res = await getDuyurularAction();
    if (res.success) {
      setDuyurular(res.duyurular);
      setCanManage(res.canManage);
    } else {
      setMessage({ text: res.error || "Duyurular yüklenemedi.", type: "error" });
    }
    setLoading(false);
  }

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!baslik.trim() || !icerik.trim()) return;

    setSubmitLoading(true);
    setMessage(null);

    const res = await createDuyuruAction({ action: "create", baslik, icerik });
    if (res.success) {
      setMessage({ text: "Duyuru başarıyla yayınlandı.", type: "success" });
      setBaslik("");
      setIcerik("");
      await loadDuyurular(); // Listeyi yenile
    } else {
      setMessage({ text: res.error || "Eklenirken bir hata oluştu.", type: "error" });
    }
    setSubmitLoading(false);

    // Otomatik mesaj gizleme
    setTimeout(() => setMessage(null), 4000);
  };

  const handleToggle = async (duyuruId: number) => {
    const res = await toggleDuyuruAction({ action: "toggle", duyuru_id: duyuruId });
    if (res.success) {
      setMessage({ text: res.message, type: "success" });
      await loadDuyurular();
    } else {
      setMessage({ text: res.error || "İşlem başarısız oldu.", type: "error" });
    }
    setTimeout(() => setMessage(null), 4000);
  };

  const formatDate = (dateString: string) => {
    const d = new Date(dateString);
    return `${d.toLocaleDateString("tr-TR")} ${d.toLocaleTimeString("tr-TR", { hour: "2-digit", minute: "2-digit" })}`;
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[40vh] text-emerald-500 font-bold">
        Duyurular Yükleniyor...
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            <Megaphone className="w-6 h-6 text-emerald-400" /> Duyurular
          </h1>
          <p className="text-zinc-500 text-sm mt-1">Sistem üzerindeki aktif duyuruları buradan takip edebilirsiniz.</p>
        </div>
      </div>

      <AnimatePresence>
        {message && (
          <motion.div
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -10 }}
            className={`p-4 rounded-xl flex items-center gap-3 border ${
              message.type === "success" 
                ? "bg-emerald-500/10 border-emerald-500/20 text-emerald-400" 
                : "bg-red-500/10 border-red-500/20 text-red-400"
            }`}
          >
            {message.type === "success" ? <CheckCircle2 className="w-5 h-5" /> : <XCircle className="w-5 h-5" />}
            <span className="text-sm font-medium">{message.text}</span>
          </motion.div>
        )}
      </AnimatePresence>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {/* DUYURU LİSTESİ */}
        <div className={`space-y-4 ${canManage ? 'lg:col-span-2' : 'lg:col-span-3'}`}>
          <div className="bg-zinc-900 border border-white/5 rounded-2xl overflow-hidden">
            <div className="p-5 border-b border-white/5 bg-zinc-900/50">
              <h2 className="font-semibold text-white">Yayında Olan Duyurular</h2>
            </div>
            
            <div className="p-2 space-y-2">
              {duyurular.length === 0 ? (
                <div className="p-6 text-center text-zinc-500 text-sm">
                  Henüz bir duyuru bulunmuyor.
                </div>
              ) : (
                duyurular.map((duyuru, idx) => (
                  <motion.div 
                    initial={{ opacity: 0, scale: 0.98 }}
                    animate={{ opacity: 1, scale: 1 }}
                    transition={{ delay: idx * 0.05 }}
                    key={duyuru.duyuru_id} 
                    className={`p-5 rounded-xl border border-white/5 transition-all ${
                      Number(duyuru.aktif) === 1 ? "bg-zinc-800/40 hover:bg-zinc-800" : "bg-zinc-950 opacity-70 border-dashed"
                    }`}
                  >
                    <div className="flex justify-between items-start gap-4">
                      <div className="space-y-3 flex-1">
                        <div>
                          <h3 className="text-zinc-200 font-bold flex items-center gap-2">
                            {duyuru.baslik}
                            {Number(duyuru.aktif) === 0 && (
                              <span className="text-[10px] px-2 py-0.5 rounded-full bg-zinc-700 text-zinc-300 font-medium">Taslak</span>
                            )}
                          </h3>
                          <div className="flex items-center gap-3 text-[11px] text-zinc-500 mt-1">
                            <span className="flex items-center gap-1"><User className="w-3 h-3" /> {duyuru.olusturan || 'Sistem'}</span>
                            <span className="w-1 h-1 rounded-full bg-zinc-700"></span>
                            <span className="flex items-center gap-1"><Clock className="w-3 h-3" /> {formatDate(duyuru.olusturma_tarihi)}</span>
                          </div>
                        </div>
                        <p className="text-sm text-zinc-400 leading-relaxed whitespace-pre-wrap">
                          {duyuru.icerik}
                        </p>
                      </div>
                      
                      {canManage && (
                        <div>
                          <button 
                            onClick={() => handleToggle(duyuru.duyuru_id)}
                            className={`text-[11px] px-3 py-1.5 rounded-lg font-medium transition-colors border ${
                              Number(duyuru.aktif) === 1 
                                ? "bg-zinc-800 text-zinc-400 border-white/10 hover:bg-zinc-700 hover:text-white" 
                                : "bg-emerald-500/10 text-emerald-400 border-emerald-500/20 hover:bg-emerald-500/20"
                            }`}
                          >
                            {Number(duyuru.aktif) === 1 ? "Taslağa Al" : "Yayınla"}
                          </button>
                        </div>
                      )}
                    </div>
                  </motion.div>
                ))
              )}
            </div>
          </div>
        </div>

        {/* YÖNETİM (YENİ EKLE) BÖLÜMÜ */}
        {canManage && (
          <div className="lg:col-span-1">
            <div className="bg-zinc-900 border border-white/5 rounded-2xl overflow-hidden sticky top-24">
              <div className="p-5 border-b border-white/5 flex items-center gap-2 bg-zinc-900/50">
                <PenLine className="w-5 h-5 text-emerald-400" />
                <h2 className="font-semibold text-white">Yeni Duyuru</h2>
              </div>
              <form onSubmit={handleCreate} className="p-5 space-y-4">
                <div className="space-y-1.5">
                  <label className="text-xs font-semibold text-zinc-400 ml-1">Başlık</label>
                  <input 
                    type="text" 
                    value={baslik}
                    onChange={(e) => setBaslik(e.target.value)}
                    required
                    placeholder="Örn: Haftalık Toplantı"
                    className="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-3 text-sm text-zinc-200 focus:outline-none focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/50 transition-all placeholder:text-zinc-600"
                  />
                </div>
                
                <div className="space-y-1.5">
                  <label className="text-xs font-semibold text-zinc-400 ml-1">İçerik</label>
                  <textarea 
                    rows={5}
                    value={icerik}
                    onChange={(e) => setIcerik(e.target.value)}
                    required
                    placeholder="Duyuru metnini buraya yazın..."
                    className="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-3 text-sm text-zinc-200 focus:outline-none focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/50 transition-all placeholder:text-zinc-600 resize-none"
                  ></textarea>
                </div>
                
                <button 
                  type="submit" 
                  disabled={submitLoading || !baslik.trim() || !icerik.trim()}
                  className="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-medium py-3 rounded-xl transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <Send className="w-4 h-4" />
                  {submitLoading ? "Genişletiliyor..." : "Yayınla"}
                </button>
              </form>
            </div>
          </div>
        )}

      </div>
    </div>
  );
}
