"use client";

import QuoteList from "@/components/quotes/QuoteList";
import Link from "next/link";

export default function ClientDevisPage() {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Mes demandes de devis</h1>
        <Link
          href="/recherche"
          className="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700"
        >
          + Nouvelle demande
        </Link>
      </div>
      <QuoteList mode="client" />
    </div>
  );
}
