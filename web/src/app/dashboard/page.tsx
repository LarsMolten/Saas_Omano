"use client";

import { useUser } from "@/lib/hooks/useUser";
import Link from "next/link";
import { useEffect, useState } from "react";

interface DashboardStats {
  pending_quotes: number;
  total_services: number;
  total_portfolio: number;
  average_rating: string;
}

export default function DashboardOverview() {
  const { user } = useUser();
  const [stats, setStats] = useState<DashboardStats | null>(null);

  useEffect(() => {
    Promise.all([
      fetch("/api/v1/quotes").then((r) => (r.ok ? r.json() : [])),
      fetch(`/api/v1/providers/${user?.id}/services`).then((r) => (r.ok ? r.json() : [])),
      fetch(`/api/v1/providers/${user?.id}/portfolio`).then((r) => (r.ok ? r.json() : [])),
    ]).then(([quotes, services, portfolio]) => {
      setStats({
        pending_quotes: Array.isArray(quotes)
          ? quotes.filter((q: { status: string }) => q.status === "pending").length
          : 0,
        total_services: Array.isArray(services) ? services.length : 0,
        total_portfolio: Array.isArray(portfolio) ? portfolio.length : 0,
        average_rating: user?.average_rating ?? "0",
      });
    }).catch(() => {});
  }, [user]);

  if (!user) return null;

  const cards = [
    { label: "Devis en attente", value: stats?.pending_quotes ?? "-", href: "/dashboard/devis", color: "bg-amber-50 text-amber-700" },
    { label: "Services publies", value: stats?.total_services ?? "-", href: "/dashboard/services", color: "bg-blue-50 text-blue-700" },
    { label: "Realisations", value: stats?.total_portfolio ?? "-", href: "/dashboard/portfolio", color: "bg-green-50 text-green-700" },
    { label: "Note moyenne", value: stats ? parseFloat(stats.average_rating).toFixed(1) : "-", href: "/dashboard/statistiques", color: "bg-purple-50 text-purple-700" },
  ];

  return (
    <div className="space-y-8">
      <h1 className="text-2xl font-bold text-gray-900">Vue d&apos;ensemble</h1>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {cards.map((card) => (
          <Link
            key={card.label}
            href={card.href}
            className={`${card.color} rounded-xl p-5 hover:shadow-md transition-shadow`}
          >
            <p className="text-sm font-medium opacity-75">{card.label}</p>
            <p className="text-3xl font-bold mt-1">{card.value}</p>
          </Link>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white rounded-xl border p-6">
          <h2 className="font-semibold text-gray-900 mb-4">Actions rapides</h2>
          <div className="space-y-2">
            <Link href="/dashboard/profil" className="block text-sm text-blue-600 hover:underline">
              &rarr; Modifier mon profil
            </Link>
            <Link href="/dashboard/services" className="block text-sm text-blue-600 hover:underline">
              &rarr; Ajouter un service
            </Link>
            <Link href="/dashboard/portfolio" className="block text-sm text-blue-600 hover:underline">
              &rarr; Ajouter une realisation
            </Link>
            <Link href="/dashboard/abonnement" className="block text-sm text-blue-600 hover:underline">
              &rarr; Gerer mon abonnement
            </Link>
          </div>
        </div>

        <div className="bg-white rounded-xl border p-6">
          <h2 className="font-semibold text-gray-900 mb-4">Infos du compte</h2>
          <dl className="space-y-2 text-sm">
            <div className="flex justify-between">
              <dt className="text-gray-500">Role</dt>
              <dd className="font-medium text-gray-900 capitalize">{user.role}</dd>
            </div>
            <div className="flex justify-between">
              <dt className="text-gray-500">Categorie</dt>
              <dd className="font-medium text-gray-900">{user.category || "-"}</dd>
            </div>
            <div className="flex justify-between">
              <dt className="text-gray-500">Ville</dt>
              <dd className="font-medium text-gray-900">{user.city || "-"}</dd>
            </div>
            <div className="flex justify-between">
              <dt className="text-gray-500">Email verifie</dt>
              <dd className={`font-medium ${user.email_verified_at ? "text-green-600" : "text-red-600"}`}>
                {user.email_verified_at ? "Oui" : "Non"}
              </dd>
            </div>
          </dl>
        </div>
      </div>
    </div>
  );
}
