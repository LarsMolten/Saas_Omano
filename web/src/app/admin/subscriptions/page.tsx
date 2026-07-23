"use client";

import { useState, useEffect, useCallback } from "react";
import type { AdminSubscription, PaginatedResponse } from "@/lib/types/admin";

export default function AdminSubscriptionsPage() {
  const [data, setData] = useState<PaginatedResponse<AdminSubscription> | null>(null);
  const [plan, setPlan] = useState("");
  const [status, setStatus] = useState("");
  const [loading, setLoading] = useState(true);

  const fetchSubs = useCallback(async () => {
    setLoading(true);
    const params = new URLSearchParams();
    if (plan) params.set("plan", plan);
    if (status) params.set("status", status);
    const res = await fetch(`/api/v1/admin/subscriptions?${params}`);
    if (res.ok) setData(await res.json());
    setLoading(false);
  }, [plan, status]);

  useEffect(() => {
    fetchSubs();
  }, [fetchSubs]);

  async function updateSub(id: number, newStatus: string) {
    await fetch(`/api/v1/admin/subscriptions/${id}`, {
      method: "PATCH",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ status: newStatus }),
    });
    fetchSubs();
  }

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900">Abonnements</h1>

      <div className="flex gap-3">
        <select
          value={plan}
          onChange={(e) => setPlan(e.target.value)}
          className="rounded-lg border border-gray-300 px-3 py-2 text-sm"
        >
          <option value="">Tous les plans</option>
          <option value="starter">Starter</option>
          <option value="pro">Pro</option>
          <option value="premium">Premium</option>
        </select>
        <select
          value={status}
          onChange={(e) => setStatus(e.target.value)}
          className="rounded-lg border border-gray-300 px-3 py-2 text-sm"
        >
          <option value="">Tous les statuts</option>
          <option value="active">Actif</option>
          <option value="expired">Expire</option>
          <option value="cancelled">Annule</option>
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
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500">Prestataire</th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500">Plan</th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500">Periode</th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500">Statut</th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500">Debut</th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500">Fin</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {data?.data.map((sub) => (
                <tr key={sub.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 text-sm">
                    <div className="font-medium text-gray-900">{sub.provider_name}</div>
                    <div className="text-gray-500">{sub.provider_email}</div>
                  </td>
                  <td className="px-4 py-3">
                    <span
                      className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                        sub.plan === "premium"
                          ? "bg-amber-100 text-amber-700"
                          : sub.plan === "pro"
                            ? "bg-blue-100 text-blue-700"
                            : "bg-gray-100 text-gray-600"
                      }`}
                    >
                      {sub.plan}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-sm text-gray-600">{sub.period}</td>
                  <td className="px-4 py-3">
                    <span
                      className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                        sub.status === "active"
                          ? "bg-green-100 text-green-700"
                          : sub.status === "expired"
                            ? "bg-gray-100 text-gray-500"
                            : "bg-red-100 text-red-700"
                      }`}
                    >
                      {sub.status}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-sm text-gray-500">
                    {new Date(sub.starts_at).toLocaleDateString("fr-FR")}
                  </td>
                  <td className="px-4 py-3 text-sm text-gray-500">
                    {new Date(sub.ends_at).toLocaleDateString("fr-FR")}
                  </td>
                  <td className="px-4 py-3 text-right">
                    <div className="flex justify-end gap-1">
                      {sub.status === "active" && (
                        <button
                          onClick={() => updateSub(sub.id, "cancelled")}
                          className="rounded px-2 py-1 text-xs text-red-600 hover:bg-red-50"
                        >
                          Annuler
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {data?.data.length === 0 && (
            <p className="py-8 text-center text-sm text-gray-500">Aucun abonnement</p>
          )}
        </div>
      )}
    </div>
  );
}
