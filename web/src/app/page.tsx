import type { Metadata } from "next";
import Link from "next/link";
import type { HomepageData } from "@/lib/types/homepage";

export const metadata: Metadata = {
  title: "Omano - Annuaire de prestataires evenementiels a Oman",
  description:
    "Trouvez les meilleurs prestataires pour votre evenement a Oman. Traiteurs, photographes, DJs et plus encore.",
};

const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api";

async function getHomepageData(): Promise<HomepageData> {
  try {
    const res = await fetch(`${API_BASE}/v1/homepage`, {
      next: { revalidate: 3600 },
    });
    if (!res.ok) {
      return { featured: [], categories: [], recent: [] };
    }
    return res.json() as Promise<HomepageData>;
  } catch {
    return { featured: [], categories: [], recent: [] };
  }
}

function StarRating({ rating }: { rating: string }) {
  const value = parseFloat(rating);
  if (value === 0) return null;
  return (
    <span className="flex items-center gap-1 text-sm text-amber-600">
      <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
      </svg>
      {value.toFixed(1)}
    </span>
  );
}

function ProviderCardSmall({
  provider,
}: {
  provider: HomepageData["featured"][0] | HomepageData["recent"][0];
}) {
  const rating = parseFloat(provider.average_rating);
  return (
    <Link
      href={`/prestataire/${provider.slug}`}
      className="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-blue-200 transition-all"
    >
      <div className="flex items-start justify-between mb-2">
        <h3 className="font-semibold text-gray-900 truncate">
          {provider.name}
        </h3>
        {provider.email_verified_at && (
          <span className="ml-2 shrink-0 text-green-500">
            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path
                fillRule="evenodd"
                d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0c.46.308.99.463 1.528.463a3.066 3.066 0 011.528.463 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clipRule="evenodd"
              />
            </svg>
          </span>
        )}
      </div>
      {provider.category && (
        <span className="inline-block text-xs bg-blue-50 text-blue-700 rounded-full px-2.5 py-0.5 mb-2">
          {provider.category}
        </span>
      )}
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
        {rating > 0 && <StarRating rating={provider.average_rating} />}
      </div>
      {provider.services.length > 0 && (
        <div className="mt-3 flex flex-wrap gap-1">
          {provider.services.slice(0, 2).map((s) => (
            <span
              key={s.id}
              className="text-xs bg-gray-50 text-gray-500 rounded-full px-2 py-0.5"
            >
              {s.title}
            </span>
          ))}
        </div>
      )}
    </Link>
  );
}

const CATEGORY_ICONS: Record<string, string> = {
  Traiteur: "\uD83C\uDF5D",
  Decoration: "\uD83C\uDF80",
  Photographe: "\uD83D\uDCF7",
  "DJ & Musique": "\uD83C\uDFB6",
  "Wedding Planner": "\uD83D\uD92D",
  Fleuriste: "\uD83C\uDF38",
  Patissier: "\uD83C\uDF70",
  Sonorisation: "\uD83D\uDD0A",
  Videaste: "\uD83C\uDFAC",
  Maquillage: "\uD83C\uDF38",
  Coiffure: "\u2702\uFE0F",
  "Location materiel": "\uD83D\uDCE6",
};

export default async function HomePage() {
  const data = await getHomepageData();

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero */}
      <section className="bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 text-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28">
          <div className="max-w-3xl">
            <h1 className="text-4xl sm:text-5xl font-bold leading-tight mb-6">
              Trouvez le prestataire ideal pour votre evenement
            </h1>
            <p className="text-lg sm:text-xl text-blue-100 mb-8">
              Des centaines de professionnels verifies a Oman, pret a
              transformer votre evenement en moment inoubliable.
            </p>
            <div className="flex flex-col sm:flex-row gap-3">
              <Link
                href="/recherche"
                className="inline-flex items-center justify-center bg-white text-blue-700 font-semibold px-6 py-3 rounded-lg hover:bg-blue-50 transition-colors"
              >
                <svg
                  className="w-5 h-5 mr-2"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                  />
                </svg>
                Rechercher un prestataire
              </Link>
              <Link
                href="/register"
                className="inline-flex items-center justify-center border border-white/30 text-white font-semibold px-6 py-3 rounded-lg hover:bg-white/10 transition-colors"
              >
                Devenir prestataire
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* Categories */}
      {data.categories.length > 0 && (
        <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
          <h2 className="text-2xl font-bold text-gray-900 mb-8">
            Par categories
          </h2>
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            {data.categories.map((cat) => (
              <Link
                key={cat.id}
                href={`/categories/${cat.slug}`}
                className="bg-white rounded-xl border border-gray-100 p-4 text-center hover:shadow-md hover:border-blue-200 transition-all group"
              >
                <span className="text-3xl block mb-2">
                  {CATEGORY_ICONS[cat.name] || "\uD83C\uDFAD"}
                </span>
                <span className="font-medium text-gray-900 text-sm group-hover:text-blue-600 transition-colors">
                  {cat.name}
                </span>
                <span className="block text-xs text-gray-400 mt-1">
                  {cat.provider_count} prestataire
                  {cat.provider_count !== 1 ? "s" : ""}
                </span>
              </Link>
            ))}
          </div>
        </section>
      )}

      {/* Featured / Premium */}
      {data.featured.length > 0 && (
        <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
          <div className="flex items-center justify-between mb-8">
            <div>
              <h2 className="text-2xl font-bold text-gray-900">
                Prestataires Premium
              </h2>
              <p className="text-gray-500 mt-1">
                Les meilleurs professionnels, verifies et recommends
              </p>
            </div>
            <Link
              href="/recherche"
              className="text-sm text-blue-600 hover:text-blue-700 font-medium hidden sm:block"
            >
              Voir tous &rarr;
            </Link>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {data.featured.map((p) => (
              <ProviderCardSmall key={p.id} provider={p} />
            ))}
          </div>
        </section>
      )}

      {/* Recent */}
      {data.recent.length > 0 && (
        <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
          <div className="flex items-center justify-between mb-8">
            <h2 className="text-2xl font-bold text-gray-900">
              Derniers prestataires inscrits
            </h2>
            <Link
              href="/recherche"
              className="text-sm text-blue-600 hover:text-blue-700 font-medium hidden sm:block"
            >
              Voir tous &rarr;
            </Link>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {data.recent.map((p) => (
              <ProviderCardSmall key={p.id} provider={p} />
            ))}
          </div>
        </section>
      )}

      {/* CTA */}
      <section className="bg-gray-900 text-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
          <h2 className="text-2xl font-bold mb-4">
            Vous etes prestataire ?
          </h2>
          <p className="text-gray-300 mb-6 max-w-xl mx-auto">
            Inscrivez-vous gratuitement et commencez a recevoir des demandes
            de devis des des aujourd'hui.
          </p>
          <Link
            href="/register"
            className="inline-flex items-center bg-blue-600 text-white font-semibold px-6 py-3 rounded-lg hover:bg-blue-500 transition-colors"
          >
            Creer mon compte gratuit
          </Link>
        </div>
      </section>
    </div>
  );
}
