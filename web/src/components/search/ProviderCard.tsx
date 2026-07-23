import Link from "next/link";
import type { SearchProvider } from "@/lib/types/search";

interface ProviderCardProps {
  provider: SearchProvider;
}

export default function ProviderCard({ provider }: ProviderCardProps) {
  const rating = parseFloat(provider.average_rating);
  const isVerified = !!provider.email_verified_at;

  return (
    <Link
      href={`/prestataire/${provider.slug}`}
      className="block bg-white rounded-lg shadow-sm border overflow-hidden hover:shadow-md hover:border-blue-200 transition-all"
    >
      <div className="p-4">
        <div className="flex items-start justify-between">
          <div className="flex-1 min-w-0">
            <h3 className="font-semibold text-gray-900 truncate">
              {provider.name}
            </h3>
            {provider.category && (
              <span className="inline-block mt-1 text-xs bg-blue-100 text-blue-800 rounded px-2 py-0.5">
                {provider.category}
              </span>
            )}
          </div>
          {isVerified && (
            <span className="ml-2 text-green-500 text-xs font-medium flex items-center gap-1">
              <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path
                  fillRule="evenodd"
                  d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                  clipRule="evenodd"
                />
              </svg>
              Verifie
            </span>
          )}
        </div>

        {provider.bio && (
          <p className="mt-2 text-sm text-gray-600 line-clamp-2">
            {provider.bio}
          </p>
        )}

        <div className="mt-3 flex items-center gap-4 text-sm text-gray-500">
          {provider.city && (
            <span className="flex items-center gap-1">
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              {provider.city}
            </span>
          )}

          {rating > 0 && (
            <span className="flex items-center gap-1">
              <svg className="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
              {rating.toFixed(1)}
            </span>
          )}

          {provider.distance_km !== undefined && (
            <span>{provider.distance_km.toFixed(1)} km</span>
          )}
        </div>

        {provider.services.length > 0 && (
          <div className="mt-3 flex flex-wrap gap-1">
            {provider.services.slice(0, 3).map((s) => (
              <span
                key={s.id}
                className="text-xs bg-gray-100 text-gray-600 rounded px-2 py-0.5"
              >
                {s.title}
                {s.price && ` - ${s.price} OMR`}
              </span>
            ))}
            {provider.services.length > 3 && (
              <span className="text-xs text-gray-400">
                +{provider.services.length - 3} autres
              </span>
            )}
          </div>
        )}
      </div>
    </Link>
  );
}
