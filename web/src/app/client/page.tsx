"use client";

import { useUser } from "@/lib/hooks/useUser";
import Link from "next/link";

export default function ClientOverview() {
  const { user } = useUser();

  if (!user) return null;

  return (
    <div className="space-y-8">
      <h1 className="text-2xl font-bold text-gray-900">Mon espace</h1>

      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <Link
          href="/client/devis"
          className="bg-white rounded-xl border p-5 hover:shadow-md transition-shadow"
        >
          <p className="text-sm text-gray-500">Mes devis</p>
          <p className="text-2xl font-bold text-gray-900 mt-1">Consulter</p>
        </Link>
        <Link
          href="/client/favoris"
          className="bg-white rounded-xl border p-5 hover:shadow-md transition-shadow"
        >
          <p className="text-sm text-gray-500">Mes favoris</p>
          <p className="text-2xl font-bold text-gray-900 mt-1">Consulter</p>
        </Link>
        <Link
          href="/client/avis"
          className="bg-white rounded-xl border p-5 hover:shadow-md transition-shadow"
        >
          <p className="text-sm text-gray-500">Mes avis</p>
          <p className="text-2xl font-bold text-gray-900 mt-1">Consulter</p>
        </Link>
      </div>

      <div className="bg-white rounded-xl border p-6">
        <h2 className="font-semibold text-gray-900 mb-3">Actions rapides</h2>
        <div className="space-y-2">
          <Link href="/recherche" className="block text-sm text-blue-600 hover:underline">
            &rarr; Trouver un prestataire
          </Link>
          <Link href="/client/devis" className="block text-sm text-blue-600 hover:underline">
            &rarr; Envoyer une demande de devis
          </Link>
        </div>
      </div>
    </div>
  );
}
