"use client";

import { useState, useEffect, useRef } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Users, Search, Trash2, Edit, UserPlus, FileSpreadsheet, AlertCircle, CheckCircle2, XCircle, ShieldCheck } from "lucide-react";
import { getAdminUsersAction, deleteAdminUserAction, saveAdminUserAction } from "../../../actions/auth";
import Link from "next/link";

export default function AdminKullanicilarPage() {
  const [users, setUsers] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  
  // Pagination
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalItems, setTotalItems] = useState(0);

  // Filters
  const [search, setSearch] = useState("");
  const [debounceSearch, setDebounceSearch] = useState("");
  const [roleFilter, setRoleFilter] = useState("");
  const [bykFilter, setBykFilter] = useState("");
  const [statusFilter, setStatusFilter] = useState("1");
  
  // Constants
  const [roles, setRoles] = useState<any[]>([]);
  const [byks, setByks] = useState<any[]>([]);

  const [message, setMessage] = useState<{ text: string; type: "success" | "error" } | null>(null);

  // Debounce search effect
  useEffect(() => {
    const handler = setTimeout(() => {
      setDebounceSearch(search);
      setPage(1); // Reset page on new search
    }, 500);
    return () => clearTimeout(handler);
  }, [search]);

  useEffect(() => {
    loadUsers();
  }, [page, debounceSearch, roleFilter, bykFilter, statusFilter]);

  async function loadUsers() {
    setLoading(true);
    const res = await getAdminUsersAction({ 
      page, 
      search: debounceSearch, 
      rol: roleFilter, 
      byk: bykFilter, 
      status: statusFilter 
    });

    if (res.success) {
      setUsers(res.users);
      setTotalPages(res.totalPages);
      setTotalItems(res.total);
      if (res.constants) {
        setRoles(res.constants.roles || []);
        setByks(res.constants.byks || []);
      }
    } else {
      setMessage({ text: res.error || "Kullanıcılar yüklenemedi.", type: "error" });
    }
    setLoading(false);
  }

  const showMessage = (text: string, type: "success" | "error") => {
    setMessage({ text, type });
    setTimeout(() => setMessage(null), 4000);
  };

  const handleDelete = async (id: number, name: string) => {
    if (!confirm(`"${name}" isimli kullanıcıyı tamamen silmek istediğinize emin misiniz? Bu işlem geri alınamaz!`)) return;

    const res = await deleteAdminUserAction(id);
    if (res.success) {
      showMessage(res.message, "success");
      await loadUsers();
    } else {
      showMessage(res.error, "error");
    }
  };

  // Modal State
  const [modalOpen, setModalOpen] = useState(false);
  const [selectedUser, setSelectedUser] = useState<any>(null);
  const [isSubmitLoading, setIsSubmitLoading] = useState(false);
  const [formData, setFormData] = useState({
    ad: "",
    soyad: "",
    email: "",
    password: "",
    rol_id: "",
    byk_id: "",
    telefon: "",
    divan_uyesi: 0,
    aktif: 1
  });

  const openModal = (user: any = null) => {
    if (user) {
      setSelectedUser(user);
      setFormData({
        ad: user.ad,
        soyad: user.soyad,
        email: user.email,
        password: "", // Leave blank for edit
        rol_id: user.rol_id,
        byk_id: user.byk_id || "",
        telefon: user.telefon || "",
        divan_uyesi: user.divan_uyesi == 1 ? 1 : 0,
        aktif: user.aktif == 1 ? 1 : 0
      });
    } else {
      setSelectedUser(null);
      setFormData({
        ad: "",
        soyad: "",
        email: "",
        password: "",
        rol_id: "",
        byk_id: "",
        telefon: "",
        divan_uyesi: 0,
        aktif: 1
      });
    }
    setModalOpen(true);
  };

  const closeModal = () => {
    setModalOpen(false);
    setSelectedUser(null);
  };

  const handleSaveUser = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitLoading(true);

    try {
      const data = {
        action: selectedUser ? "update" : "create",
        kullanici_id: selectedUser?.kullanici_id,
        ...formData
      };

      const res = await saveAdminUserAction(data);
      if (res.success) {
        showMessage(res.message || "Kullanıcı kaydedildi.", "success");
        closeModal();
        await loadUsers();
      } else {
        showMessage(res.error || "Bir hata oluştu.", "error");
      }
    } catch (err: any) {
      showMessage(err.message || "Bağlantı hatası.", "error");
    } finally {
      setIsSubmitLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* BAŞLIK */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            <Users className="w-6 h-6 text-emerald-400" /> Kullanıcı Yönetimi
          </h1>
          <p className="text-zinc-500 text-sm mt-1">Tüm sistem kullanıcılarını, görevlerini ve yetkilerini yönetin. (Toplam: {totalItems})</p>
        </div>
        <div className="flex gap-2">
          <button className="bg-zinc-800 hover:bg-zinc-700 text-zinc-300 font-medium px-4 py-2 rounded-xl transition-all flex items-center gap-2 text-sm border border-white/5 disabled:opacity-50">
            <FileSpreadsheet className="w-4 h-4" /> Dışa Aktar
          </button>
          <button onClick={() => openModal()} className="bg-emerald-600 hover:bg-emerald-500 text-white font-medium px-4 py-2 rounded-xl transition-all flex items-center gap-2 text-sm shadow-lg shadow-emerald-500/20 disabled:opacity-50">
            <UserPlus className="w-4 h-4" /> Yeni Ekle
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

      {/* USER MODAL */}
      <AnimatePresence>
        {modalOpen && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <motion.div 
              initial={{ scale: 0.9, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.9, opacity: 0 }}
              className="bg-zinc-900 border border-white/10 rounded-3xl p-6 w-full max-w-2xl shadow-2xl relative overflow-hidden"
            >
              <button onClick={closeModal} className="absolute top-4 right-4 text-zinc-500 hover:text-white transition-colors">
                <XCircle className="w-6 h-6" />
              </button>
              
              <h2 className="text-xl font-bold text-white mb-6 flex items-center gap-3">
                 {selectedUser ? <Edit className="w-6 h-6 text-sky-400" /> : <UserPlus className="w-6 h-6 text-emerald-400" />}
                 {selectedUser ? "Kullanıcıyı Düzenle" : "Yeni Kullanıcı Ekle"}
              </h2>

              <form onSubmit={handleSaveUser} className="space-y-5">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                  <div className="space-y-2">
                    <label className="text-xs font-bold text-zinc-500 uppercase">Ad</label>
                    <input 
                      required
                      type="text" 
                      value={formData.ad}
                      onChange={(e) => setFormData({...formData, ad: e.target.value})}
                      className="w-full bg-zinc-950 border border-white/5 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500/50 transition-colors"
                    />
                  </div>
                  <div className="space-y-2">
                    <label className="text-xs font-bold text-zinc-500 uppercase">Soyad</label>
                    <input 
                      required
                      type="text" 
                      value={formData.soyad}
                      onChange={(e) => setFormData({...formData, soyad: e.target.value})}
                      className="w-full bg-zinc-950 border border-white/5 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500/50 transition-colors"
                    />
                  </div>
                  <div className="space-y-2 md:col-span-2">
                    <label className="text-xs font-bold text-zinc-500 uppercase">E-posta</label>
                    <input 
                      required
                      type="email" 
                      value={formData.email}
                      onChange={(e) => setFormData({...formData, email: e.target.value})}
                      className="w-full bg-zinc-950 border border-white/5 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500/50 transition-colors"
                    />
                  </div>
                  <div className="space-y-2">
                    <label className="text-xs font-bold text-zinc-500 uppercase">{selectedUser ? "Yeni Şifre (Değiştirmek istemiyorsanız boş bırakın)" : "Şifre"}</label>
                    <input 
                      required={!selectedUser}
                      type="password" 
                      value={formData.password}
                      onChange={(e) => setFormData({...formData, password: e.target.value})}
                      className="w-full bg-zinc-950 border border-white/5 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500/50 transition-colors"
                    />
                  </div>
                  <div className="space-y-2">
                    <label className="text-xs font-bold text-zinc-500 uppercase">Telefon</label>
                    <input 
                      type="text" 
                      placeholder="06..."
                      value={formData.telefon}
                      onChange={(e) => setFormData({...formData, telefon: e.target.value})}
                      className="w-full bg-zinc-950 border border-white/5 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500/50 transition-colors"
                    />
                  </div>
                  <div className="space-y-2">
                    <label className="text-xs font-bold text-zinc-500 uppercase">Rol</label>
                    <select 
                      required
                      value={formData.rol_id}
                      onChange={(e) => setFormData({...formData, rol_id: e.target.value})}
                      className="w-full bg-zinc-950 border border-white/5 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500/50 transition-colors"
                    >
                       <option value="">Seçiniz...</option>
                       {roles.map(r => <option key={r.rol_id} value={r.rol_id}>{r.rol_adi}</option>)}
                    </select>
                  </div>
                  <div className="space-y-2">
                    <label className="text-xs font-bold text-zinc-500 uppercase">Bağlı BYK</label>
                    <select 
                      value={formData.byk_id}
                      onChange={(e) => setFormData({...formData, byk_id: e.target.value})}
                      className="w-full bg-zinc-950 border border-white/5 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500/50 transition-colors"
                    >
                       <option value="">Genel (Bölge)</option>
                       {byks.map(b => <option key={b.byk_id} value={b.byk_id}>{b.byk_adi}</option>)}
                    </select>
                  </div>
                </div>

                <div className="flex flex-wrap gap-6 pt-2">
                  <label className="flex items-center gap-3 cursor-pointer group">
                     <div className="relative flex items-center">
                        <input 
                          type="checkbox" 
                          checked={formData.divan_uyesi == 1}
                          onChange={(e) => setFormData({...formData, divan_uyesi: e.target.checked ? 1 : 0})}
                          className="w-5 h-5 rounded border-white/10 bg-zinc-950 text-emerald-500 focus:ring-emerald-500 focus:ring-offset-zinc-900"
                        />
                     </div>
                     <span className="text-sm font-medium text-zinc-300 group-hover:text-white transition-colors">Divan Üyesi</span>
                  </label>
                  <label className="flex items-center gap-3 cursor-pointer group">
                     <div className="relative flex items-center">
                        <input 
                          type="checkbox" 
                          checked={formData.aktif == 1}
                          onChange={(e) => setFormData({...formData, aktif: e.target.checked ? 1 : 0})}
                          className="w-5 h-5 rounded border-white/10 bg-zinc-950 text-emerald-500 focus:ring-emerald-500 focus:ring-offset-zinc-900"
                        />
                     </div>
                     <span className="text-sm font-medium text-zinc-300 group-hover:text-white transition-colors">Hesap Aktif</span>
                  </label>
                </div>

                <div className="flex justify-end gap-3 pt-4">
                  <button type="button" onClick={closeModal} className="px-6 py-3 rounded-xl text-zinc-400 hover:bg-white/5 transition-all text-sm font-bold">İptal</button>
                  <button 
                    disabled={isSubmitLoading} 
                    type="submit" 
                    className="bg-emerald-600 hover:bg-emerald-500 text-white px-8 py-3 rounded-xl transition-all text-sm font-bold shadow-lg shadow-emerald-500/20 disabled:opacity-50"
                  >
                    {isSubmitLoading ? "Kaydediliyor..." : selectedUser ? "Güncelle" : "Oluştur"}
                  </button>
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* FİLTRELER */}
      <div className="bg-zinc-900 border border-white/5 rounded-2xl p-4 shadow-xl shadow-black/20 flex flex-wrap gap-4 items-end">
        <div className="flex-1 min-w-[200px]">
          <label className="text-xs font-semibold text-zinc-500 mb-1.5 block">Arama</label>
          <div className="relative">
            <Search className="w-4 h-4 absolute left-3 top-3 text-zinc-400" />
            <input 
              type="text" 
              placeholder="Ad, Soyad, E-posta..." 
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full bg-zinc-950 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-emerald-500/50 transition-colors"
            />
          </div>
        </div>

        <div className="w-[150px]">
          <label className="text-xs font-semibold text-zinc-500 mb-1.5 block">Rol</label>
          <select 
            value={roleFilter}
            onChange={(e) => { setRoleFilter(e.target.value); setPage(1); }}
            className="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-emerald-500/50"
          >
            <option value="">Tümü</option>
            {roles.map(r => (
              <option key={r.rol_id} value={r.rol_adi}>{r.rol_adi}</option>
            ))}
          </select>
        </div>

        <div className="w-[150px]">
          <label className="text-xs font-semibold text-zinc-500 mb-1.5 block">BYK</label>
          <select 
            value={bykFilter}
            onChange={(e) => { setBykFilter(e.target.value); setPage(1); }}
            className="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-emerald-500/50"
          >
             <option value="">Tümü</option>
            {byks.map(b => (
              <option key={b.byk_id} value={b.byk_id}>{b.byk_adi}</option>
            ))}
          </select>
        </div>

        <div className="w-[120px]">
          <label className="text-xs font-semibold text-zinc-500 mb-1.5 block">Durum</label>
          <select 
            value={statusFilter}
            onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}
            className="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-emerald-500/50"
          >
             <option value="1">Aktif</option>
             <option value="0">Pasif</option>
             <option value="all">Farketmez</option>
          </select>
        </div>
        
        {(search || roleFilter || bykFilter || statusFilter !== '1') && (
           <button 
             onClick={() => {
                setSearch(""); setDebounceSearch(""); setRoleFilter(""); setBykFilter(""); setStatusFilter("1"); setPage(1);
             }}
             className="bg-zinc-800 hover:bg-zinc-700 text-zinc-400 p-2.5 rounded-xl transition-all"
             title="Filtreleri Temizle"
           >
             <XCircle className="w-5 h-5" />
           </button>
        )}
      </div>

      {/* LİSTE */}
      <div className="bg-zinc-900 border border-white/5 rounded-2xl overflow-hidden min-h-[500px] flex flex-col">
        {loading ? (
          <div className="flex-1 flex justify-center items-center py-20">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-500"></div>
          </div>
        ) : users.length === 0 ? (
           <div className="flex-1 flex flex-col items-center justify-center py-20 opacity-50">
             <AlertCircle className="w-12 h-12 text-zinc-500 mb-3" />
             <p className="text-zinc-400">Bu filtrelere uygun kullanıcı bulunamadı.</p>
           </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm whitespace-nowrap">
              <thead className="bg-zinc-950/50 text-zinc-400 font-medium">
                <tr>
                  <th className="px-5 py-4 pl-6">Ad Soyad</th>
                  <th className="px-5 py-4">İletişim</th>
                  <th className="px-5 py-4">Rol</th>
                  <th className="px-5 py-4">BYK</th>
                  <th className="px-5 py-4 text-right pr-6">İşlemler</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-white/5">
                {users.map((u) => (
                  <tr key={u.kullanici_id} className="hover:bg-white/[0.02] transition-colors group">
                    <td className="px-5 py-4 pl-6">
                       <div className="flex items-center gap-3">
                          <div className={`relative w-9 h-9 rounded-full flex items-center justify-center font-bold text-white shadow-inner ${u.olusturma_tarihi ? 'bg-zinc-800' : 'bg-gradient-to-br from-emerald-500 to-teal-600'}`}>
                             {u.ad.charAt(0)}{u.soyad.charAt(0)}
                             {u.aktif == 1 && <span className="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 border-2 border-zinc-900 rounded-full"></span>}
                             {u.aktif == 0 && <span className="absolute bottom-0 right-0 w-2.5 h-2.5 bg-zinc-500 border-2 border-zinc-900 rounded-full"></span>}
                          </div>
                          <div>
                            <div className="font-bold text-zinc-200">
                               {u.ad} {u.soyad}
                               {u.divan_uyesi == 1 && <span className="ml-2 text-[10px] bg-amber-500/20 text-amber-500 px-1.5 py-0.5 rounded uppercase font-bold tracking-wider">Divan</span>}
                            </div>
                            {u.gorev_adi && <div className="text-[10px] text-zinc-500 mt-0.5">{u.gorev_adi}</div>}
                          </div>
                       </div>
                    </td>
                    <td className="px-5 py-4">
                       <div className="text-zinc-300">{u.email}</div>
                       {u.telefon && <div className="text-[11px] text-zinc-500">{u.telefon}</div>}
                    </td>
                    <td className="px-5 py-4">
                       {u.rol_adi === 'Süper Admin' ? (
                          <span className="inline-flex font-bold text-[11px] bg-red-500/10 text-red-400 border border-red-500/20 px-2 py-1 rounded">SÜPER ADMİN</span>
                       ) : (
                          <span className="inline-flex font-semibold text-[11px] bg-zinc-800 text-zinc-300 border border-white/5 px-2 py-1 rounded uppercase tracking-wider">{u.rol_adi}</span>
                       )}
                    </td>
                    <td className="px-5 py-4">
                       {u.tum_byklar ? (
                          <span className="inline-flex font-semibold text-[11px] bg-zinc-950 text-zinc-400 border border-white/5 px-2 py-1 rounded truncate max-w-[150px]" title={u.tum_byklar}>{u.tum_byklar}</span>
                       ) : u.byk_adi && u.byk_adi !== '-' ? (
                          <div className="flex items-center gap-1.5">
                             <div className="w-2 h-2 rounded-full" style={{ backgroundColor: u.byk_renk || '#10b981' }}></div>
                             <span className="font-medium text-zinc-300 text-xs">{u.byk_adi}</span>
                             {u.byk_kodu && <span className="text-[10px] text-zinc-600">({u.byk_kodu})</span>}
                          </div>
                       ) : (
                          <span className="text-zinc-600">-</span>
                       )}
                    </td>
                    <td className="px-5 py-4 text-right pr-6 space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                       {/* DÜZENLEME VE YETKİ BUTONLARI HAZIRLIĞI */}
                       {u.rol_adi !== 'Süper Admin' && (
                          <button title="Kullanıcı Yetkileri" className="p-1.5 bg-amber-500/10 hover:bg-amber-500/20 text-amber-500 rounded-lg transition-colors">
                             <ShieldCheck className="w-4 h-4" />
                          </button>
                       )}
                       <button onClick={() => openModal(u)} title="Düzenle" className="p-1.5 bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 rounded-lg transition-colors">
                          <Edit className="w-4 h-4" />
                       </button>
                       <button onClick={() => handleDelete(u.kullanici_id, `${u.ad} ${u.soyad}`)} title="Sil" className="p-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-500 rounded-lg transition-colors">
                          <Trash2 className="w-4 h-4" />
                       </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* PAGINATION */}
        {!loading && totalPages > 1 && (
           <div className="p-4 border-t border-white/5 bg-zinc-900/50 flex justify-center mt-auto">
              <div className="flex gap-1">
                 {Array.from({ length: totalPages }, (_, i) => i + 1).map(pNum => {
                    // Simple pagination display logic
                    if (pNum === 1 || pNum === totalPages || (pNum >= page - 2 && pNum <= page + 2)) {
                        return (
                          <button 
                            key={pNum} 
                            onClick={() => setPage(pNum)}
                            className={`w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold transition-all ${
                               page === pNum ? "bg-emerald-600 text-white" : "bg-zinc-950 text-zinc-400 hover:text-white border border-white/5"
                            }`}
                          >
                            {pNum}
                          </button>
                        );
                    }
                    if (pNum === page - 3 || pNum === page + 3) {
                       return <span key={pNum} className="w-6 h-8 flex items-center justify-center text-zinc-600">...</span>;
                    }
                    return null;
                 })}
              </div>
           </div>
        )}
      </div>
    </div>
  );
}
