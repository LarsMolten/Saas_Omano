import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import type { CategoryPageResponse } from "@/lib/types/category";

const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api";

async function getCategoryData(
  slug: string
): Promise<CategoryPageResponse | null> {
  try {
    const res = await fetch(`${API_BASE}/v1/categories/slug/${slug}`, {
      next: { revalidate: 3600 },
    });
    if (!res.ok) return null;
    return res.json() as Promise<CategoryPageResponse>;
  } catch {
    return null;
  }
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const data = await getCategoryData(slug);
  if (!data) return { title: "Categorie introuvable" };

  return {
    title: `${data.category.name} - Prestataires a Oman | Omano`,
    description: `Trouvez les meilleurs prestataires en ${data.category.name} a Oman. Consultez les profils, services et avis.`,
  };
}

export default async function CategoryPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const data = await getCategoryData(slug);

  if (!data) notFound();

  const { category, providers } = data;

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <nav className="text-sm text-gray-500 mb-3">
            <Link href="/" className="hover:text-gray-700">
              Accueil
            </Link>
            <span className="mx-2">/</span>
            <Link href="/recherche" className="hover:text-gray-700">
              Recherche
            </Link>
            <span className="mx-2">/</span>
            <span className="text-gray-900">{category.name}</span>
          </nav>
          <h1 className="text-3xl font-bold text-gray-900">{category.name}</h1>
          <p className="mt-2 text-gray-600">
            {providers.meta.total} prestataire
            {providers.meta.total !== 1 ? "s" : ""} dans cette categorie
          </p>
        </div>
      </div>

      {/* Providers Grid */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {providers.data.length === 0 ? (
          <div className="text-center py-16">
            <p className="text-gray-500 text-lg">
              Aucun prestataire dans cette categorie pour le moment.
            </p>
            <Link
              href="/recherche"
              className="mt-4 inline-block text-blue-600 hover:text-blue-700 font-medium"
            >
              Explorer toutes les categories
            </Link>
          </div>
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {providers.data.map((provider) => {
              const rating = parseFloat(provider.average_rating);
              return (
                <Link
                  key={provider.id}
                  href={`/prestataire/${provider.slug}`}
                  className="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-blue-200 transition-all"
                >
                  <div className="flex items-start justify-between mb-2">
                    <h3 className="font-semibold text-gray-900 truncate">
                      {provider.name}
                    </h3>
                    {provider.email_verified_at && (
                      <span className="ml-2 shrink-0 text-green-500">
                        <svg
                          className="w-4 h-4"
                          fill="currentColor"
                          viewBox="0 0 20 20"
                        >
                          <path
                            fillRule="evenodd"
                            d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clipRule="evenodd"
                          />
                        </svg>
                      </span>
                    )}
                  </div>
                  {provider.bio && (
                    <p className="text-sm text-gray-600 line-clamp-2 mb-3">
                      {provider.bio}
                    </p>
                  )}
                  <div className="flex items-center gap-3 text-sm text-gray-500">
                    {provider.city && (
                      <span className="flex items-center gap-1">
                        <svg
                          className="w-3.5 h-3.5"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                          />
                          <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                          />
                        </svg>
                        {provider.city}
                      </span>
                    )}
                    {rating > 0 && (
                      <span className="flex items-center gap-1">
                        <svg
                          className="w-4 h-4 text-amber-400"
                          fill="currentColor"
                          viewBox="0 0 20 20"
                        >
                          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        {rating.toFixed(1)}
                      </span>
                    )}
                  </div>
                  {provider.services.length > 0 && (
                    <div className="mt-3 flex flex-wrap gap-1">
                      {provider.services.slice(0, 3).map((s) => (
                        <span
                          key={s.id}
                          className="text-xs bg-gray-50 text-gray-500 rounded-full px-2.5 py-0.5"
                        >
                          {s.title}
                          {s.price && ` - ${s.price} OMR`}
                        </span>
                      ))}
                    </div>
                  )}
                </Link>
              );
            })}
          </div>
        )}

        {providers.meta.last_page > 1 && (
          <div className="flex justify-center gap-2 mt-8">
            {Array.from({ length: providers.meta.last_page }, (_, i) => i + 1)
              .filter(
                (p) =>
                  p === 1 ||
                  p === providers.meta.last_page ||
                  Math.abs(p - providers.meta.current_page) <= 2
              )
              .reduce<(number | "ellipsis")[]>((acc, p, i, arr) => {
                if (i > 0 && p - (arr[i - 1] as number) > 1) {
                  acc.push("ellipsis");
                }
                acc.push(p);
                return acc;
              }, [])
              .map((item, i) =>
                item === "ellipsis" ? (
                  <span key={`e${i}`} className="px-3 py-2 text-gray-400">
                    ...
                  </span>
                ) : (
                  <Link
                    key={item}
                    href={`/categories/${slug}?page=${item}`}
                    className={`px-3 py-2 rounded text-sm font-medium ${
                      item === providers.meta.current_page
                        ? "bg-blue-600 text-white"
                        : "bg-white border hover:bg-gray-50"
                    }`}
                  >
                    {item}
                  </Link>
                )
              )}
          </div>
        )}
      </div>
    </div>
  );
}
