"use client";

import type { Service } from "@/lib/types/service";

interface ServiceListProps {
  services: Service[];
}

function formatPrice(price: string | null, priceType: string): string {
  if (priceType === "quote") return "Sur devis";
  if (!price) return "";

  const num = parseFloat(price);
  if (priceType === "from") return `À partir de ${num.toFixed(2)} €`;
  return `${num.toFixed(2)} €`;
}

export default function ServiceList({ services }: ServiceListProps) {
  if (services.length === 0) {
    return (
      <p className="text-gray-500 text-center py-8">
        Aucune prestation publiée.
      </p>
    );
  }

  return (
    <div className="space-y-4">
      {services.map((service) => (
        <div
          key={service.id}
          className="border border-gray-200 rounded-lg p-4 hover:shadow-sm transition-shadow"
        >
          <div className="flex justify-between items-start">
            <div className="flex-1">
              <h3 className="font-semibold text-gray-900">{service.title}</h3>
              {service.description && (
                <p className="text-sm text-gray-600 mt-1">
                  {service.description}
                </p>
              )}
            </div>
            <span className="text-blue-600 font-bold whitespace-nowrap ml-4">
              {formatPrice(service.price, service.price_type)}
            </span>
          </div>

          {service.options.length > 0 && (
            <div className="mt-3 pt-3 border-t border-gray-100">
              <p className="text-xs text-gray-500 uppercase tracking-wide mb-1">
                Options
              </p>
              <div className="flex flex-wrap gap-2">
                {service.options.map((option) => (
                  <span
                    key={option.id}
                    className="inline-flex items-center text-sm bg-gray-100 rounded-full px-3 py-1"
                  >
                    {option.label}
                    {parseFloat(option.extra_price) > 0 && (
                      <span className="ml-1 text-gray-500">
                        +{parseFloat(option.extra_price).toFixed(2)} €
                      </span>
                    )}
                  </span>
                ))}
              </div>
            </div>
          )}
        </div>
      ))}
    </div>
  );
}
