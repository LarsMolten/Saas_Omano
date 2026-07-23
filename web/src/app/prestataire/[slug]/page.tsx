import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import type { ProviderProfileResponse } from "@/lib/types/provider";

const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api";
const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || "http://localhost:3000";

async function getProvider(
  slug: string
): Promise<ProviderProfileResponse | null> {
  try {
    const res = await fetch(`${API_BASE}/v1/providers/slug/${slug}`, {
      next: { revalidate: 600 },
    });
    if (!res.ok) return null;
    return res.json() as Promise<ProviderProfileResponse>;
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
  const data = await getProvider(slug);
  if (!data) return { title: "Prestataire introuvable" };

  const { provider } = data;
  const title = `${provider.name} - ${provider.category || "Prestataire"} a ${provider.city || "Oman"} | Omano`;
  const description =
    provider.bio ||
    `${provider.name} est ${provider.category || "un prestataire"} a ${provider.city || "Oman"}. Consultez ses services, realisations et avis.`;

  return {
    title,
    description,
    openGraph: {
      title,
      description,
      url: `${SITE_URL}/prestataire/${provider.slug}`,
      type: "profile",
    },
  };
}

function StarRating({ rating, count }: { rating: string; count: number }) {
  const value = parseFloat(rating);
  return (
    <div className="flex items-center gap-2">
      <div className="flex">
        {[1, 2, 3, 4, 5].map((i) => (
          <svg
            key={i}
            className={`w-5 h-5 ${i <= Math.round(value) ? "text-amber-400" : "text-gray-200"}`}
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
          </svg>
        ))}
      </div>
      <span className="text-sm font-medium text-gray-900">
        {value.toFixed(1)}
      </span>
      <span className="text-sm text-gray-500">
        ({count} avis{count !== 1 ? "" : ""})
      </span>
    </div>
  );
}

function PriceDisplay({
  price,
  priceType,
}: {
  price: string | null;
  priceType: string;
}) {
  if (!price || priceType === "quote") {
    return (
      <span className="text-sm text-gray-500 italic">Sur devis</span>
    );
  }
  return (
    <span className="text-lg font-semibold text-gray-900">
      {priceType === "from" ? "A partir de " : ""}
      {parseFloat(price).toFixed(2)} OMR
    </span>
  );
}

export default async function ProviderProfilePage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const data = await getProvider(slug);

  if (!data) notFound();

  const { provider, services, portfolio, reviews } = data;

  const schemaJsonLd = {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    name: provider.name,
    description: provider.bio,
    url: `${SITE_URL}/prestataire/${provider.slug}`,
    address: provider.city
      ? {
          "@type": "PostalAddress",
          addressLocality: provider.city,
          addressCountry: "OM",
        }
      : undefined,
    aggregateRating:
      provider.rating_count > 0
        ? {
            "@type": "AggregateRating",
            ratingValue: parseFloat(provider.average_rating),
            reviewCount: provider.rating_count,
            bestRating: 5,
          }
        : undefined,
    priceRange: services.some((s) => s.price)
      ? `${Math.min(
          ...services.filter((s) => s.price).map((s) => parseFloat(s.price!))
        ).toFixed(2)} OMR - ${Math.max(
          ...services.filter((s) => s.price).map((s) => parseFloat(s.price!))
        ).toFixed(2)} OMR`
      : undefined,
  };

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(schemaJsonLd) }}
      />

      <div className="min-h-screen bg-gray-50">
        {/* Header */}
        <div className="bg-white border-b">
          <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div className="flex items-start justify-between">
              <div>
                <div className="flex items-center gap-3 mb-2">
                  <h1 className="text-3xl font-bold text-gray-900">
                    {provider.name}
                  </h1>
                  {provider.email_verified_at && (
                    <span className="text-green-500" title="Profil verifie">
                      <svg
                        className="w-6 h-6"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                      >
                        <path
                          fillRule="evenodd"
                          d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0c.46.308.99.463 1.528.463a3.066 3.066 0 011.528.463 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                          clipRule="evenodd"
                        />
                      </svg>
                    </span>
                  )}
                </div>
                <div className="flex items-center gap-4 text-sm text-gray-500 mb-3">
                  {provider.category && (
                    <span className="bg-blue-50 text-blue-700 rounded-full px-3 py-1 font-medium">
                      {provider.category}
                    </span>
                  )}
                  {provider.city && (
                    <span className="flex items-center gap-1">
                      <svg
                        className="w-4 h-4"
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
                </div>
                <StarRating
                  rating={provider.average_rating}
                  count={provider.rating_count}
                />
              </div>
              <Link
                href={`/prestataire/${provider.slug}#devis`}
                className="bg-blue-600 text-white font-semibold px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors shrink-0"
              >
                Demander un devis
              </Link>
            </div>
          </div>
        </div>

        {/* Content */}
        <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          {/* Bio */}
          {provider.bio && (
            <div className="bg-white rounded-xl border p-6 mb-8">
              <h2 className="text-lg font-semibold text-gray-900 mb-3">
                A propos
              </h2>
              <p className="text-gray-600 leading-relaxed">{provider.bio}</p>
            </div>
          )}

          {/* Services */}
          {services.length > 0 && (
            <div className="bg-white rounded-xl border p-6 mb-8">
              <h2 className="text-lg font-semibold text-gray-900 mb-4">
                Services
              </h2>
              <div className="space-y-4">
                {services.map((service) => (
                  <div
                    key={service.id}
                    className="border border-gray-100 rounded-lg p-4"
                  >
                    <div className="flex items-start justify-between mb-2">
                      <h3 className="font-medium text-gray-900">
                        {service.title}
                      </h3>
                      <PriceDisplay
                        price={service.price}
                        priceType={service.price_type}
                      />
                    </div>
                    {service.description && (
                      <p className="text-sm text-gray-600 mb-2">
                        {service.description}
                      </p>
                    )}
                    {service.options.length > 0 && (
                      <div className="flex flex-wrap gap-2 mt-2">
                        {service.options.map((opt) => (
                          <span
                            key={opt.id}
                            className="text-xs bg-gray-50 text-gray-600 rounded-full px-3 py-1"
                          >
                            {opt.label}
                            {parseFloat(opt.extra_price) > 0 &&
                              ` (+${parseFloat(opt.extra_price).toFixed(2)} OMR)`}
                          </span>
                        ))}
                      </div>
                    )}
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Portfolio */}
          {portfolio.length > 0 && (
            <div className="bg-white rounded-xl border p-6 mb-8">
              <h2 className="text-lg font-semibold text-gray-900 mb-4">
                Realisations
              </h2>
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                {portfolio.map((item) => (
                  <div
                    key={item.id}
                    className="border border-gray-100 rounded-lg overflow-hidden"
                  >
                    {item.media.length > 0 &&
                      item.media[0].type === "image" && (
                        <div className="aspect-[4/3] bg-gray-100 flex items-center justify-center">
                          <svg
                            className="w-10 h-10 text-gray-300"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                          >
                            <path
                              strokeLinecap="round"
                              strokeLinejoin="round"
                              strokeWidth={1.5}
                              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                          </svg>
                        </div>
                      )}
                    <div className="p-3">
                      <h3 className="font-medium text-gray-900 text-sm">
                        {item.title}
                      </h3>
                      {item.location && (
                        <p className="text-xs text-gray-500 mt-1">
                          {item.location}
                        </p>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Reviews */}
          <div className="bg-white rounded-xl border p-6 mb-8">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">
              Avis clients
            </h2>
            {reviews.data.length === 0 ? (
              <p className="text-gray-500 text-sm">
                Aucun avis pour le moment.
              </p>
            ) : (
              <div className="space-y-4">
                {reviews.data.map((review) => (
                  <div
                    key={review.id}
                    className="border-b border-gray-50 pb-4 last:border-0 last:pb-0"
                  >
                    <div className="flex items-center justify-between mb-1">
                      <span className="font-medium text-gray-900 text-sm">
                        {review.user.name}
                      </span>
                      <div className="flex">
                        {[1, 2, 3, 4, 5].map((i) => (
                          <svg
                            key={i}
                            className={`w-4 h-4 ${i <= review.rating ? "text-amber-400" : "text-gray-200"}`}
                            fill="currentColor"
                            viewBox="0 0 20 20"
                          >
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                          </svg>
                        ))}
                      </div>
                    </div>
                    {review.comment && (
                      <p className="text-sm text-gray-600">{review.comment}</p>
                    )}
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* CTA Devis */}
          <div
            id="devis"
            className="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl p-8 text-white text-center"
          >
            <h2 className="text-xl font-bold mb-2">
              Besoin de {provider.name} ?
            </h2>
            <p className="text-blue-100 mb-4">
              Envoyez une demande de devis et recevez une reponse sous 24h.
            </p>
            <Link
              href={`/mes-devis?provider=${provider.id}`}
              className="inline-flex items-center bg-white text-blue-700 font-semibold px-6 py-3 rounded-lg hover:bg-blue-50 transition-colors"
            >
              Demander un devis
            </Link>
          </div>
        </div>
      </div>
    </>
  );
}
