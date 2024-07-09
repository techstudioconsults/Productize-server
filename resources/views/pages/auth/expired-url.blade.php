<x-layout>
    <x-slot:title>UnAuthorized</x-slot>
        <div class="flex justify-between items-center flex-1 px-[105px] gap-3">
            <div class="flex justify-start items-start flex-col gap-14 flex-1">
                <div class="flex justify-start items-start flex-col gap-2 text-[#1A2E35] text-5xl">
                    <p class="font-bold">Ooops...</p>
                    <p class="font-normal">Link Expired or Invalid</p>
                </div>
                <div>
                    <a href="https://tsa-productize.vercel.app/dashboard/home" class="py-2 px-6 border border-[#6D5DD3] rounded-[4px] cursor-pointer text-[#6D5DD3] hover:animate-pulse hover:bg-[#6D5DD3] hover:text-[#ffffff] hover:border-none">Go To Dashboard</a>
                </div>
            </div>
            <div class="flex-1">
                <img class="w-full h-full object-contain" src="{{asset('asset/404.png')}}" alt="404">
            </div>
        </div>
</x-layout>
