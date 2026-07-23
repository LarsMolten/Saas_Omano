"use client";

import { useState, useEffect, useCallback } from "react";
import type { AdminUser, PaginatedResponse } from "@/lib/types/admin";

export default function AdminUsersPage() {
  const [data, setData] = useState<PaginatedResponse<AdminUser> | null>(null);
  const [search, setSearch] = useState("");
  const [role, setRole] = useState("");
  const [status, setStatus] = useState("");
  const [loading, setLoading] = useState(true);

  const fetchUsers = useCallback(async () => {
    setLoading(true);
    const params = new URLSearchParams();
    if (search) params.set("search", search);
    if (role) params.set("role", role);
    if (status) params.set("status", status);

    const res = await fetch(`/api/v1/admin/users?${params}`);
    if (res.ok) setData(await res.json());
    setLoading(false);
  }, [search, role, status]);

  useEffect(() => { fetchUsers(); }, [fetchUsers]);

  async function updateUser(userId: number, patch: Record<string, string>) {
    await fetch("/api/v1/admin/users/update", {
      method: "PATCH",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ user_id: userId, ...patch }),
    });
    fetchUsers();
  }

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900">Utilisateurs</h1>

      <div className="flex flex-wrap gap-3">
        <input
          type="text"
          placeholder="Rechercher..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
        />
        <select value={role} onChange={(e) => setRole(e.target.value)} className="rounded-lg border border-gray-300 px-3 py-2 text-sm">
          <option value="">Tous les rôles</option>
          <option value="client">Client</option>
          <option value="prestataire">Prestataire</option>
          <option value="admin">Admin</option>
        </select>
        <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-lg border border-gray-300 px-3 py-2 text-sm">
          <option value="">Tous les statuts</option>
          <option value="active">Actif</option>
          <option value="suspended">Suspendu</option>
          <option value="banned">Banni</option>
        </select>
      </div>

      {loading ? (
        <div className="flex h-32 items-center justify-center">
          <div className="h-6 w-6 animate-spin rounded-full border-4 border-indigo-600 border-t-transparent" />
        </div>
      ) : (
        <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500">Nom</th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500">Email</th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500">Rôle</th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500">Statut</th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500">Inscrit le</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {data?.data.map((u) => (
                <tr key={u.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 text-sm font-medium text-gray-900">{u.name}</td>
                  <td className="px-4 py-3 text-sm text-gray-600">{u.email}</td>
                  <td className="px-4 py-3">
                    <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                      u.role === "admin" ? "bg-purple-100 text-purple-700" :
                      u.role === "prestataire" ? "bg-blue-100 text-blue-700" :
                      "bg-gray-100 text-gray-700"
                    }`}>{u.role}</span>
                  </td>
                  <td className="px-4 py-3">
                    <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                      u.status === "active" ? "bg-green-100 text-green-700" :
                      u.status === "suspended" ? "bg-amber-100 text-amber-700" :
                      "bg-red-100 text-red-700"
                    }`}>{u.status}</span>
                  </td>
                  <td className="px-4 py-3 text-sm text-gray-500">{new Date(u.created_at).toLocaleDateString("fr-FR")}</td>
                  <td className="px-4 py-3 text-right">
                    <div className="flex justify-end gap-1">
                      {u.status === "active" && u.role !== "admin" && (
                        <button onClick={() => updateUser(u.id, { status: "suspended" })} className="rounded px-2 py-1 text-xs text-amber-600 hover:bg-amber-50">Suspendre</button>
                      )}
                      {u.status === "suspended" && (
                        <button onClick={() => updateUser(u.id, { status: "active" })} className="rounded px-2 py-1 text-xs text-green-600 hover:bg-green-50">Réactiver</button>
                      )}
                      {u.status !== "banned" && u.role !== "admin" && (
                        <button onClick={() => updateUser(u.id, { status: "banned" })} className="rounded px-2 py-1 text-xs text-red-600 hover:bg-red-50">Bannir</button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {data?.data.length === 0 && <p className="py-8 text-center text-sm text-gray-500">Aucun utilisateur trouvé</p>}
        </div>
      )}
    </div>
  );
}
