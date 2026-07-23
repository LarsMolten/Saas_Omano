import { NextRequest, NextResponse } from "next/server";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

export async function PATCH(request: NextRequest) {
  const cookie = request.headers.get("cookie") ?? "";
  const body = await request.json();

  const res = await fetch(`${API_URL}/v1/admin/users`, {
    method: "PATCH",
    headers: { Cookie: cookie, Accept: "application/json", "Content-Type": "application/json" },
    body: JSON.stringify(body),
  });

  const data = await res.json();
  return NextResponse.json(data, { status: res.status });
}
