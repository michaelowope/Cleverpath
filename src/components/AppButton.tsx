import { Button } from "./ui/button";

interface AppButtonProps {
  title: string;
  className?: string;
  onClick?: () => void;
}

export default function AppButton({
  title,
  className,
  onClick,
}: AppButtonProps) {
  function handleClick() {
    if (onClick) {
      onClick();
    }
  }

  return (
    <Button
      onClick={handleClick}
      className={`${className || ""} bg-white text-blue-900 hover:text-blue-950 border border-blue-900 px-4 py-2 cursor-pointer hover:bg-white rounded-md shadow outline-none capitalize font-bold`}
    >
      {title}
    </Button>
  );
}
