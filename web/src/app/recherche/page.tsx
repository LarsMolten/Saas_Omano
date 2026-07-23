import type { Metadata } from "next";
import SearchPage from "@/components/search/SearchPage";
import type { SearchResponse } from "@/lib/types/search";

export const metadata: Metadata = {
  title: "Recherche de prestataires | Omano",
  description:
    "Trouvez les meilleurs prestataires pour votre evenement a Oman. Filtrez par categorie, ville, prix et note.",
};

const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api";

async function getInitialResults(): Promise<SearchResponse> {
  try {
    const res = await fetch(`${API_BASE}/v1/search?per_page=20`, {
      next: { revalidate: 300 },
    });

    if (!res.ok) {
      return { data: [], meta: { current_page: 1, last_page: 1, per_page: 20, total: 0 } };
    }

    return res.json() as Promise<SearchResponse>;
  } catch {
    return { data: [], meta: { current_page: 1, last_page: 1, per_page: 20, total: 0 } };
  }
}

export default async function RecherchePage() {
  const initial = await getInitialResults();

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="bg-white border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <h1 className="text-3xl font-bold text-gray-900">
            Recherche de prestataires
          </h1>
          <p className="mt-2 text-gray-600">
            Explorez les meilleurs professionnels pour votre evenement a Oman
          </p>
        </div>
      </div>
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <SearchPage initialData={initial} />
      </main>
    </div>
  );
}
