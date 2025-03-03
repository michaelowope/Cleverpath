import AppButton from "@/components/AppButton";
import { ArrowLeft } from "lucide-react";

export default function NotFound() {
  function goBack() {
    window.history.back();
  }

  return (
    <section className="w-[100dvw] h-[100dvh] flex items-center justify-center bg-blue-900 flex-col gap-3">
      <h1 className="text-4xl text-white font-extrabold">404 Page Not Found</h1>
      <AppButton
        title="Go back"
        onClick={goBack}
        leftIcon={<ArrowLeft size={32} />}
      />
    </section>
  );
}
