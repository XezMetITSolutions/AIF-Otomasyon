"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { ShieldCheck, Search, Save, CheckCircle2, XCircle, AlertCircle, Info } from "lucide-react";
import { getAdminYetkilerAction, saveAdminYetkilerAction } from "../../../actions/auth";

export default function AdminYetkilerPage() {
  const [users, setUsers] = useState<any[]>([]);
  const [modules, setModules] = useState<any>({});
  const [loading, setLoading] = useState(true);
  const [submitLoading, setSubmitLoading] = useState(false);
  
  // Grid/Matrix State
  const [permissions, setPermissions] = useState<Record<string, Record<string, boolean>>>({});
  
  const [search, setSearch] = useState("");
  const [message, setMessage] = useState<{ text: string; type: "success" | "error" } | null>(null);

  useEffect(() => {
    loadData();
  }, []);

  async function loadData() {
    setLoading(true);
    const res = await getAdminYetkilerAction();
    if (res.success) {
      setUsers(res.users || []);
      setModules(res.modules || {});
      
      // Initialize Matrix
      const initMatrix: Record<string, Record<string, boolean>> = {};
      res.users?.forEach((u: any) => {
        initMatrix[u.kullanici_id] = u.permissions || {};
      });
      setPermissions(initMatrix);
    } else {
      setMessage({ text: res.error || "Yetkiler yüklenemedi.", type: "error" });
    }
    setLoading(false);
  }

  const handleCheckboxChange = (userId: string, modKey: string, checked: boolean) => {
    setPermissions(prev => ({
      ...prev,
      [userId]: {
        ...prev[userId],
        [modKey]: checked
      }
    }));
  };

  const toggleRow = (userId: string) => {
    const userPerms = permissions[userId] || {};
    const modKeys = Object.keys(modules);
    const allChecked = modKeys.every(k => userPerms[k]);
    
    const newPerms = { ...userPerms };
    modKeys.forEach(k => {
      newPerms[k] = !allChecked;
    });

    setPermissions(prev => ({
      ...prev,
      [userId]: newPerms
    }));
  };

  const toggleColumn = (modKey: string) => {
    const allChecked = users.every(u => permissions[u.kullanici_id]?.[modKey]);
    
    setPermissions(prev => {
      const next = { ...prev };
      users.forEach(u => {
         next[u.kullanici_id] = {
            ...(next[u.kullanici_id] || {}),
            [modKey]: !allChecked
         };
      });
      return next;
    });
  };

  const handleSave = async () => {
    setSubmitLoading(true);
    const res = await saveAdminYetkilerAction(permissions);
    if (res.success) {
      setMessage({ text: res.message, type: "success" });
    } else {
      setMessage({ text: res.error || "Hata oluştu.", type: "error" });
    }
    setSubmitLoading(false);
    setTimeout(() => setMessage(null), 4000);
  };

  const filteredUsers = users.filter(u => {
     const name = `${u.ad} ${u.soyad}`.toLowerCase();
     return name.includes(search.toLowerCase()) || (u.gorev_adi || '').toLowerCase().includes(search.toLowerCase());
  });

  return (
    <div className="space-y-6">
      {/* BAŞLIK */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            <ShieldCheck className="w-6 h-6 text-emerald-400" /> Panel Yetkilendirme
          </h1>
          <p className="text-zinc-500 text-sm mt-1">Matris görünümüyle tüm üyelerin modül yetkilerini bulk yönetin.</p>
        </div>
        <div>
          <button 
            onClick={handleSave} 
            disabled={submitLoading || loading}
            className="w-full md:w-auto bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-6 py-2.5 rounded-xl transition-all flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/20 disabled:opacity-50"
          >
            <Save className="w-4 h-4" /> {submitLoading ? "Kaydediliyor..." : "Değişiklikleri Kaydet"}
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

      <div className="bg-zinc-900 border border-white/5 rounded-2xl overflow-hidden shadow-xl flex flex-col min-h-[500px]">
        {/* TOP BAR / SEARCH */}
        <div className="p-4 border-b border-white/5 bg-zinc-950/30 flex justify-between items-center">
           <div className="relative max-w-sm w-full">
              <Search className="w-4 h-4 absolute left-3 top-3 text-zinc-500" />
              <input 
                type="text" 
                placeholder="İsim veya görev ile ara..." 
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="w-full bg-zinc-900 border border-white/10 rounded-xl pl-10 pr-4 py-2 text-sm text-zinc-300 focus:outline-none focus:border-emerald-500/50"
              />
           </div>
           <div className="flex items-center gap-1.5 text-xs text-zinc-400 bg-white/5 px-3 py-1.5 rounded-lg border border-white/5">
              <Info className="w-3.5 h-3.5 text-zinc-500" />
              Sütun başlığına basınca <span className="text-white font-bold">Bulk</span> seçer.
           </div>
        </div>

        {loading ? (
           <div className="flex-1 flex justify-center items-center py-20">
             <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-500"></div>
           </div>
        ) : (
           <div className="flex-1 overflow-x-auto">
             <table className="w-full border-collapse">
               <thead>
                 <tr className="bg-zinc-950/50">
                   <th className="sticky left-0 z-20 bg-zinc-950 p-4 border-r border-b border-white/5 min-w-[220px] text-left text-xs font-bold text-zinc-400 uppercase tracking-wider">
                      Uye / Kullanıcı
                   </th>
                   {Object.keys(modules).map(modKey => (
                     <th 
                       key={modKey} 
                       onClick={() => toggleColumn(modKey)}
                       className="p-3 border-b border-white/5 text-center min-w-[100px] cursor-pointer hover:bg-zinc-900 border-r last:border-r-0 border-white/5 group transition-colors"
                     >
                       <div className="flex flex-col items-center gap-1">
                          <span className="text-lg opacity-60 group-hover:opacity-100 flex items-center justify-center p-1.5 rounded-lg bg-white/5">
                             {/* Specific icons based on key if listed, otherwise default info icon */}
                             <ShieldCheck className="w-4 h-4" />
                          </span>
                          <span className="text-[10px] font-bold text-zinc-300 whitespace-nowrap group-hover:text-emerald-400" title={modules[modKey].description}>
                             {modules[modKey].label}
                          </span>
                       </div>
                     </th>
                   ))}
                 </tr>
               </thead>
               <tbody className="divide-y divide-white/5">
                 {filteredUsers.length === 0 ? (
                    <tr>
                       <td colSpan={Object.keys(modules).length + 1} className="p-10 text-center text-zinc-500">Kayıt Bulunamadı.</td>
                    </tr>
                 ) : (
                    filteredUsers.map(u => {
                      return (
                        <tr key={u.kullanici_id} className="hover:bg-white/[0.01] transition-colors">
                          <td 
                            onClick={() => toggleRow(u.kullanici_id)} 
                            className="sticky left-0 z-10 bg-zinc-900 p-4 border-r border-white/5 cursor-pointer hover:bg-zinc-800 transition-colors"
                          >
                             <div className="font-bold text-sm text-zinc-200">{u.ad} {u.soyad}</div>
                             <div className="text-[10px] text-zinc-500 truncate max-w-[180px]">{u.gorev_adi || '-'}</div>
                          </td>
                          {Object.keys(modules).map(modKey => {
                             const isChecked = permissions[u.kullanici_id]?.[modKey] || false;
                             return (
                                <td key={modKey} className="p-3 text-center border-r last:border-r-0 border-white/5">
                                   <div className="flex justify-center items-center">
                                      <input 
                                        type="checkbox"
                                        checked={isChecked}
                                        onChange={(e) => handleCheckboxChange(u.kullanici_id, modKey, e.target.checked)}
                                        className="w-4 h-4 rounded border-white/10 text-emerald-600 bg-zinc-950 focus:ring-emerald-500/50 cursor-pointer"
                                      />
                                   </div>
                                </td>
                             );
                          })}
                        </tr>
                      );
                    })
                 )}
               </tbody>
             </table>
           </div>
        )}
      </div>
    </div>
  );
}
