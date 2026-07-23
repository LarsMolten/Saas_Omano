"use client";

import FavoritesList from "@/components/FavoritesList";

export default function ClientFavorisPage() {
  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900">Mes favoris</h1>
      <FavoritesList />
    </div>
  );
}
