import { NextRequest, NextResponse } from "next/server";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

export async function GET(request: NextRequest) {
  const { searchParams } = new URL(request.url);

  const res = await fetch(`${API_URL}/v1/search?${searchParams.toString()}`, {
    headers: { Accept: "application/json" },
  });

  const data = await res.json();

  return NextResponse.json(data, { status: res.status });
}
