import React from "react";
import { Button } from "./ui/button";

interface AppButtonProps {
  title: string;
  className?: string;
  onClick?: () => void;
  leftIcon?: React.ReactNode;
  rightIcon?: React.ReactNode;
}

export default function AppButton({
  title,
  className,
  onClick,
  leftIcon,
  rightIcon,
}: AppButtonProps) {
  function handleClick() {
    if (onClick) {
      onClick();
    }
  }

  return (
    <Button
      onClick={handleClick}
      className={`${className || ""} flex items-center gap-2 bg-white text-blue-900 hover:text-blue-950 border border-blue-900 px-4 py-2 cursor-pointer hover:bg-white rounded-md shadow outline-none capitalize font-bold`}
    >
      {leftIcon && <span>{leftIcon}</span>}
      <h1>{title}</h1>
      {rightIcon && <span>{rightIcon}</span>}
    </Button>
  );
}
