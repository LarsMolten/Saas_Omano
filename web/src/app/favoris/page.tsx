import type { Metadata } from "next";
import FavoritesList from "@/components/FavoritesList";

export const metadata: Metadata = {
  title: "Mes favoris | Omano",
  description: "Vos prestataires sauvegardes.",
};

export default function FavorisPage() {
  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 py-6">
          <h1 className="text-2xl font-bold text-gray-900">Mes favoris</h1>
          <p className="mt-1 text-gray-600">Vos prestataires sauvegardes</p>
        </div>
      </header>
      <main className="max-w-7xl mx-auto px-4 py-8">
        <FavoritesList />
      </main>
    </div>
  );
}
