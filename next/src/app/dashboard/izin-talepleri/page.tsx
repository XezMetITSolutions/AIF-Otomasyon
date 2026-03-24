"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { CalendarCheck, Send, CheckCircle2, XCircle, AlertCircle, Trash2, Clock, User } from "lucide-react";
import { getIzinTalepleriAction, actionIzinTalebi } from "../../actions/auth";

export default function IzinTalepleriPage() {
  const [activeTab, setActiveTab] = useState<"talebim" | "onay">("talebim");
  const [requests, setRequests] = useState<any[]>([]);
  const [hasPermissionBaskan, setHasPermissionBaskan] = useState(false);
  const [hasPermissionUye, setHasPermissionUye] = useState(false);
  const [loading, setLoading] = useState(true);
  
  // Filters
  const [durumFilter, setDurumFilter] = useState("");

  // Form Stats
  const [baslangic, setBaslangic] = useState("");
  const [bitis, setBitis] = useState("");
  const [izinNedeni, setIzinNedeni] = useState("");
  const [aciklama, setAciklama] = useState("");
  const [submitLoading, setSubmitLoading] = useState(false);
  const [message, setMessage] = useState<{ text: string; type: "success" | "error" } | null>(null);

  // Reject Modal State
  const [rejectModalOpen, setRejectModalOpen] = useState(false);
  const [rejectId, setRejectId] = useState<number | null>(null);
  const [rejectReason, setRejectReason] = useState("");

  useEffect(() => {
    loadRequests();
  }, [activeTab, durumFilter]);

  async function loadRequests() {
    setLoading(true);
    const res = await getIzinTalepleriAction({ tab: activeTab, durum: durumFilter });
    if (res.success) {
      setRequests(res.requests || []);
      setHasPermissionBaskan(res.hasPermissionBaskan);
      setHasPermissionUye(res.hasPermissionUye);
      
      if (!res.hasPermissionUye && activeTab === 'talebim' && res.hasPermissionBaskan) {
        setActiveTab('onay');
      }
    } else {
      setMessage({ text: res.error || "Talepler yüklenemedi.", type: "error" });
    }
    setLoading(false);
  }

  const showMessage = (text: string, type: "success" | "error") => {
    setMessage({ text, type });
    setTimeout(() => setMessage(null), 4000);
  };

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!baslangic || !bitis) return;
    setSubmitLoading(true);

    const res = await actionIzinTalebi({ action: "yeni_izin", baslangic_tarihi: baslangic, bitis_tarihi: bitis, izin_nedeni: izinNedeni, aciklama });
    
    if (res.success) {
      showMessage(res.message, "success");
      setBaslangic(""); setBitis(""); setIzinNedeni(""); setAciklama("");
      await loadRequests();
    } else {
      showMessage(res.error, "error");
    }
    setSubmitLoading(false);
  };

  const handleAction = async (actionStr: string, id: number, extraData: any = {}) => {
    if (actionStr === 'delete' && !confirm("Bu talebi silmek istediğinize emin misiniz?")) return;
    if (actionStr === 'approve' && !confirm("Talebi onaylamak istediğinize emin misiniz?")) return;

    const res = await actionIzinTalebi({ action: actionStr, izin_id: id, ...extraData });
    if (res.success) {
      showMessage(res.message, "success");
      if (actionStr === 'reject') setRejectModalOpen(false);
      await loadRequests();
    } else {
      showMessage(res.error, "error");
    }
  };

  const openRejectModal = (id: number) => {
    setRejectId(id);
    setRejectReason("");
    setRejectModalOpen(true);
  };

  const submitReject = () => {
    if (!rejectId || !rejectReason.trim()) return;
    handleAction('reject', rejectId, { aciklama: rejectReason });
  };

  const getStatusBadge = (durum: string) => {
    switch (durum) {
      case 'onaylandi': return <span className="text-[10px] px-2 py-0.5 rounded-md font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20">ONAYLANDI</span>;
      case 'reddedildi': return <span className="text-[10px] px-2 py-0.5 rounded-md font-bold text-red-400 bg-red-500/10 border border-red-500/20">REDDEDİLDİ</span>;
      default: return <span className="text-[10px] px-2 py-0.5 rounded-md font-bold text-amber-400 bg-amber-500/10 border border-amber-500/20">BEKLEMEDE</span>;
    }
  };

  const calculateDays = (s: string, e: string) => {
    const start = new Date(s);
    const end = new Date(e);
    const diffTime = Math.abs(end.getTime() - start.getTime());
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString("tr-TR");
  };

  if (loading && requests.length === 0) {
    return <div className="flex items-center justify-center py-20 text-emerald-500 font-bold">Veriler Yükleniyor...</div>;
  }

  return (
    <div className="space-y-6">
      {/* HEADER SECTION */}
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            <CalendarCheck className="w-6 h-6 text-emerald-400" /> İzin Talepleri
          </h1>
          <p className="text-zinc-500 text-sm mt-1">İzin süreçlerinizi buradan yönetebilirsiniz.</p>
        </div>

        {/* TABS */}
        <div className="flex bg-zinc-950 rounded-xl p-1 border border-white/5 w-full md:w-auto">
          {hasPermissionUye && (
            <button 
              onClick={() => setActiveTab("talebim")}
              className={`flex-1 md:flex-none px-6 py-2 rounded-lg text-sm font-medium transition-all ${
                activeTab === "talebim" ? "bg-emerald-500/10 text-emerald-400 border border-emerald-500/20" : "text-zinc-400 hover:text-white"
              }`}
            >
              Taleplerim
            </button>
          )}
          {hasPermissionBaskan && (
            <button 
              onClick={() => setActiveTab("onay")}
              className={`flex-1 md:flex-none px-6 py-2 rounded-lg text-sm font-medium transition-all ${
                activeTab === "onay" ? "bg-emerald-500/10 text-emerald-400 border border-emerald-500/20" : "text-zinc-400 hover:text-white"
              }`}
            >
              Yönetim (Onay)
            </button>
          )}
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

      {/* REJECT MODAL */}
      <AnimatePresence>
        {rejectModalOpen && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <motion.div 
              initial={{ scale: 0.95, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.95, opacity: 0 }}
              className="bg-zinc-900 border border-white/10 rounded-2xl p-6 w-full max-w-md shadow-2xl"
            >
              <h3 className="text-lg font-bold text-white mb-2 flex items-center gap-2"><XCircle className="w-5 h-5 text-red-500" /> Talebi Reddet</h3>
              <p className="text-sm text-zinc-400 mb-4">Bu talebi reddetme nedeninizi aşağıda belirtin.</p>
              
              <textarea
                value={rejectReason}
                onChange={(e) => setRejectReason(e.target.value)}
                placeholder="Örn: Bu tarihlerde birimde yeterli personel bulunmuyor..."
                className="w-full bg-zinc-950 border border-white/10 rounded-xl p-3 text-sm text-white focus:border-red-500/50 outline-none min-h-[100px] mb-4"
              />
              
              <div className="flex justify-end gap-2">
                <button onClick={() => setRejectModalOpen(false)} className="px-4 py-2 rounded-xl text-sm font-medium bg-zinc-800 text-zinc-300 hover:bg-zinc-700">İptal</button>
                <button onClick={submitReject} className="px-4 py-2 rounded-xl text-sm font-medium bg-red-600 text-white hover:bg-red-500">Reddet</button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {/* TALEPLERİM EKRANI */}
        {activeTab === "talebim" && (
          <>
            <div className="lg:col-span-1">
              <div className="bg-zinc-900 border border-white/5 rounded-2xl overflow-hidden sticky top-24">
                <div className="p-5 border-b border-white/5 bg-zinc-900/50">
                  <h2 className="font-semibold text-white">Yeni İzin Talebi</h2>
                </div>
                <form onSubmit={handleCreate} className="p-5 space-y-4">
                  <div className="space-y-1.5 flex flex-col">
                    <label className="text-xs font-semibold text-zinc-400">Başlangıç Tarihi</label>
                    <input type="date" value={baslangic} onChange={(e) => setBaslangic(e.target.value)} required className="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-200 focus:border-emerald-500/50 focus:ring-1 outline-none" style={{ colorScheme: 'dark' }} />
                  </div>
                  <div className="space-y-1.5 flex flex-col">
                    <label className="text-xs font-semibold text-zinc-400">Bitiş Tarihi</label>
                    <input type="date" value={bitis} onChange={(e) => setBitis(e.target.value)} required className="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-200 focus:border-emerald-500/50 focus:ring-1 outline-none" style={{ colorScheme: 'dark' }} />
                  </div>
                  <div className="space-y-1.5 flex flex-col">
                    <label className="text-xs font-semibold text-zinc-400">Neden (Örn: Yıllık İzin, Sağlık)</label>
                    <input type="text" value={izinNedeni} onChange={(e) => setIzinNedeni(e.target.value)} maxLength={255} className="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-200 focus:border-emerald-500/50 focus:ring-1 outline-none" />
                  </div>
                  <div className="space-y-1.5 flex flex-col">
                    <label className="text-xs font-semibold text-zinc-400">Açıklama (Opsiyonel)</label>
                    <textarea rows={3} value={aciklama} onChange={(e) => setAciklama(e.target.value)} className="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-200 focus:border-emerald-500/50 focus:ring-1 outline-none resize-none"></textarea>
                  </div>
                  <button type="submit" disabled={submitLoading || !baslangic || !bitis} className="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-medium py-3 rounded-xl transition-all flex items-center justify-center gap-2">
                    <Send className="w-4 h-4" /> Talep Gönder
                  </button>
                </form>
              </div>
            </div>

            <div className="lg:col-span-2 space-y-4">
              <div className="bg-zinc-900 border border-white/5 rounded-2xl overflow-hidden min-h-[400px]">
                <div className="p-5 border-b border-white/5 bg-zinc-900/50 flex justify-between items-center">
                  <h2 className="font-semibold text-white">Geçmiş Taleplerim</h2>
                  <span className="text-xs font-bold bg-zinc-800 text-zinc-400 px-2 py-1 rounded-md">{requests.length} Kayıt</span>
                </div>
                
                <div className="p-4 space-y-3">
                  {requests.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-10 opacity-50">
                      <AlertCircle className="w-10 h-10 text-zinc-500 mb-2" />
                      <p className="text-zinc-400 text-sm">Henüz bir izin talebiniz bulunmuyor.</p>
                    </div>
                  ) : (
                    requests.map(req => (
                      <div key={req.izin_id} className="bg-zinc-950 border border-white/5 rounded-xl p-4 flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
                        <div>
                          <div className="flex items-center gap-3 mb-1">
                            {getStatusBadge(req.durum)}
                            <span className="text-sm font-bold text-zinc-200">{req.izin_nedeni || 'İzin Talebi'}</span>
                          </div>
                          <div className="flex items-center gap-2 mt-2 text-xs text-zinc-400">
                            <Clock className="w-3.5 h-3.5" /> 
                            {formatDate(req.baslangic_tarihi)} - {formatDate(req.bitis_tarihi)} 
                            <span className="text-zinc-600">({calculateDays(req.baslangic_tarihi, req.bitis_tarihi)} Gün)</span>
                          </div>
                          {req.aciklama && <p className="text-xs text-zinc-500 mt-2 line-clamp-1">{req.aciklama}</p>}
                          {req.onay_aciklama && (req.durum === 'reddedildi' || req.durum === 'onaylandi') && (
                            <p className="text-xs mt-2 p-2 rounded-lg bg-white/5 border border-white/5 flex gap-1">
                              <span className="text-zinc-400 font-bold">Yönetici Notu:</span> <span className="text-zinc-300">{req.onay_aciklama}</span>
                            </p>
                          )}
                        </div>
                      </div>
                    ))
                  )}
                </div>
              </div>
            </div>
          </>
        )}

        {/* YÖNETİM (ONAY) EKRANI */}
        {activeTab === "onay" && (
          <div className="lg:col-span-3 space-y-4">
            {/* ONAY FILTER */}
            <div className="flex gap-2">
              {[
                { label: "Tümü", val: "" },
                { label: "Bekleyenler", val: "beklemede" },
                { label: "Onaylananlar", val: "onaylandi" },
                { label: "Reddedilenler", val: "reddedildi" }
              ].map(f => (
                <button 
                  key={f.val}
                  onClick={() => setDurumFilter(f.val)}
                  className={`px-4 py-2 rounded-xl text-xs font-bold transition-all ${
                    durumFilter === f.val 
                      ? "bg-zinc-800 text-white border border-white/10" 
                      : "bg-zinc-900 border border-white/5 text-zinc-400 hover:text-zinc-200"
                  }`}
                >
                  {f.label}
                </button>
              ))}
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
              {requests.length === 0 ? (
                <div className="col-span-full py-10 flex justify-center text-zinc-500">Bu filtrede kayıt bulunamadı.</div>
              ) : (
                requests.map(req => (
                  <div key={req.izin_id} className="bg-zinc-900 border border-white/5 rounded-2xl p-5 hover:border-white/10 transition-all flex flex-col justify-between">
                    <div>
                      <div className="flex justify-between items-start mb-3">
                        {getStatusBadge(req.durum)}
                        <span className="text-xs font-semibold text-zinc-600">ID: {req.izin_id}</span>
                      </div>
                      
                      <div className="flex items-center gap-3 mb-4 border-b border-white/5 pb-4">
                        <div className="w-10 h-10 rounded-full bg-zinc-800 flex items-center justify-center font-bold text-zinc-400 shadow-inner text-sm">
                          {req.kullanici_adi?.split(' ')?.map((n: string) => n[0])?.join('') || "-"}
                        </div>
                        <div>
                          <h3 className="font-bold text-white text-sm leading-tight">{req.kullanici_adi}</h3>
                          <span className="text-[10px] text-zinc-500">{req.email}</span>
                        </div>
                      </div>

                      <div className="space-y-2 mb-4">
                        <div className="flex justify-between text-xs">
                          <span className="text-zinc-500">Neden:</span>
                          <span className="font-semibold text-zinc-300">{req.izin_nedeni || '-'}</span>
                        </div>
                        <div className="flex justify-between text-xs">
                          <span className="text-zinc-500">Tarih Aralığı:</span>
                          <span className="font-semibold text-zinc-300 text-right">
                            {formatDate(req.baslangic_tarihi)} - {formatDate(req.bitis_tarihi)}<br/>
                            <span className="text-emerald-500">({calculateDays(req.baslangic_tarihi, req.bitis_tarihi)} Gün)</span>
                          </span>
                        </div>
                        {req.aciklama && (
                          <div className="bg-zinc-950 p-2 rounded-lg text-[11px] text-zinc-400 mt-2 border border-white/5">
                            {req.aciklama}
                          </div>
                        )}
                        {req.onay_aciklama && req.durum !== 'beklemede' && (
                          <div className={`p-2 rounded-lg text-[11px] mt-2 border border-white/5 ${req.durum === 'reddedildi' ? 'bg-red-500/10 text-red-400 border-red-500/20' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'}`}>
                            <strong>Yönetici Notu:</strong> <br/>{req.onay_aciklama}
                          </div>
                        )}
                      </div>
                    </div>

                    <div className="flex gap-2 border-t border-white/5 pt-4">
                      {req.durum === 'beklemede' ? (
                        <>
                          <button onClick={() => handleAction('approve', req.izin_id)} className="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg py-2 text-xs font-bold transition-all shadow-md flex items-center justify-center gap-1">
                            <CheckCircle2 className="w-4 h-4" /> ONAYLA
                          </button>
                          <button onClick={() => openRejectModal(req.izin_id)} className="flex-1 bg-zinc-800 hover:bg-red-600 hover:text-white text-red-500 rounded-lg py-2 text-xs font-bold transition-all flex items-center justify-center gap-1">
                            <XCircle className="w-4 h-4" /> REDDET
                          </button>
                        </>
                      ) : (
                        <button onClick={() => handleAction('delete', req.izin_id)} className="w-full bg-zinc-950 hover:bg-red-500/20 hover:text-red-400 text-zinc-600 border border-white/5 rounded-lg py-2 text-xs font-bold transition-all flex items-center justify-center gap-1">
                          <Trash2 className="w-4 h-4" /> TALEBİ SİL
                        </button>
                      )}
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        )}

      </div>
    </div>
  );
}
